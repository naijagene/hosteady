<?php

namespace App\Services\Enterprise\Workflow\Designer;

use App\Models\Organization;
use App\Models\WorkflowCanvasSnapshot;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowNodeTemplate;
use App\Models\WorkflowVariable;
use App\Models\WorkflowVersion;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Data\WorkflowDefinitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowNodeData;
use App\Modules\Sdk\Workflow\Data\WorkflowTransitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowTriggerData;
use App\Modules\Sdk\Workflow\Data\WorkflowVariableData;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvas;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCloneResult;
use App\Modules\Sdk\Workflow\Designer\Enums\WorkflowCanvasStatus;
use App\Modules\Sdk\Workflow\Designer\Exceptions\WorkflowCloneException;
use App\Modules\Sdk\Workflow\Enums\WorkflowStatus;
use App\Modules\Sdk\Workflow\Enums\WorkflowVersionStatus;
use App\Services\Enterprise\Workflow\WorkflowVersionService;
use Illuminate\Support\Facades\DB;

class WorkflowCloneService
{
    public function __construct(
        private readonly WorkflowVersionService $versionService,
        private readonly WorkflowDesignerAuditRecorder $auditRecorder,
        private readonly WorkflowDesignerSearchIndexer $searchIndexer,
    ) {
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function clone(
        EnterpriseScope $scope,
        WorkflowDefinition $source,
        array $options = [],
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowCloneResult {
        $organization = Organization::query()
            ->where('public_id', $scope->organizationPublicId)
            ->firstOrFail();

        $workspaceId = null;

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $organization->id)
                ->value('id');
        }

        $baseKey = (string) ($options['workflow_key'] ?? $source->workflow_key.'_copy');
        $workflowKey = $this->resolveUniqueKey($organization->id, $workspaceId, $source->module_key, $baseKey);
        $name = (string) ($options['name'] ?? $source->name.' (Copy)');

        return DB::transaction(function () use (
            $source,
            $organization,
            $workspaceId,
            $workflowKey,
            $name,
            $userId,
            $membershipId,
        ) {
            $draftVersion = $source->versions()
                ->where('status', WorkflowVersionStatus::Draft)
                ->orderByDesc('version_number')
                ->first();

            $definitionJson = $draftVersion?->definition_json
                ?? $source->currentVersion?->definition_json
                ?? $this->defaultDefinitionJson();

            $clone = WorkflowDefinition::query()->create([
                'organization_id' => $organization->id,
                'workspace_id' => $workspaceId,
                'module_key' => $source->module_key,
                'category_id' => $source->category_id,
                'workflow_key' => $workflowKey,
                'name' => $name,
                'description' => $source->description,
                'status' => WorkflowStatus::Draft,
                'metadata' => $source->metadata ?? [],
                'created_by_user_id' => $userId,
                'created_membership_id' => $membershipId,
            ]);

            $version = $this->versionService->createDraft($clone, $definitionJson, $userId, $membershipId);
            $this->cloneVariables($source, $clone);
            $snapshotPublicId = $this->cloneLatestCanvas($source, $clone, $organization->id, $workspaceId, $version, $userId, $membershipId);

            $this->auditRecorder->recordWorkflowCloned($source, $clone);
            $this->searchIndexer->indexDefinitionBestEffort($clone);

            return new WorkflowCloneResult(
                sourceDefinitionPublicId: $source->public_id,
                clonedDefinitionPublicId: $clone->public_id,
                workflowKey: $clone->workflow_key,
                name: $clone->name,
                snapshotPublicId: $snapshotPublicId,
            );
        });
    }

    private function cloneVariables(WorkflowDefinition $source, WorkflowDefinition $clone): void
    {
        foreach ($source->variables as $variable) {
            WorkflowVariable::query()->create([
                'workflow_definition_id' => $clone->id,
                'variable_key' => $variable->variable_key,
                'label' => $variable->label,
                'type' => $variable->type,
                'default_value' => $variable->default_value,
                'is_required' => $variable->is_required,
                'metadata' => $variable->metadata,
            ]);
        }
    }

    private function cloneLatestCanvas(
        WorkflowDefinition $source,
        WorkflowDefinition $clone,
        string $organizationId,
        ?string $workspaceId,
        WorkflowVersion $version,
        ?string $userId,
        ?string $membershipId,
    ): ?string {
        $latest = WorkflowCanvasSnapshot::query()
            ->where('workflow_definition_id', $source->id)
            ->orderByDesc('created_at')
            ->first();

        if ($latest === null) {
            return null;
        }

        $snapshot = WorkflowCanvasSnapshot::query()->create([
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'workflow_definition_id' => $clone->id,
            'workflow_version_id' => $version->id,
            'canvas_json' => $latest->canvas_json,
            'status' => WorkflowCanvasStatus::Saved,
            'metadata' => $latest->metadata,
            'created_by_user_id' => $userId,
            'created_by_membership_id' => $membershipId,
        ]);

        return $snapshot->public_id;
    }

    private function resolveUniqueKey(
        string $organizationId,
        ?string $workspaceId,
        ?string $moduleKey,
        string $baseKey,
    ): string {
        $key = $baseKey;
        $attempt = 0;

        while ($this->keyExists($organizationId, $workspaceId, $moduleKey, $key)) {
            $attempt++;
            $key = $baseKey.'_'.$attempt;

            if ($attempt > 100) {
                throw new WorkflowCloneException('Unable to generate a unique workflow key for clone.');
            }
        }

        return $key;
    }

    private function keyExists(
        string $organizationId,
        ?string $workspaceId,
        ?string $moduleKey,
        string $workflowKey,
    ): bool {
        return WorkflowDefinition::query()
            ->where('organization_id', $organizationId)
            ->where('workspace_id', $workspaceId)
            ->where('module_key', $moduleKey)
            ->where('workflow_key', $workflowKey)
            ->whereNull('deleted_at')
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultDefinitionJson(): array
    {
        return [
            'nodes' => [
                ['id' => 'start', 'type' => 'start', 'label' => 'Start'],
                ['id' => 'end', 'type' => 'end', 'label' => 'End'],
            ],
            'transitions' => [
                ['id' => 'start_to_end', 'from' => 'start', 'to' => 'end'],
            ],
            'triggers' => [
                ['type' => 'manual', 'id' => 'manual_trigger'],
            ],
            'variables' => [],
        ];
    }
}
