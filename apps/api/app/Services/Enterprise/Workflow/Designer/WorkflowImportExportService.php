<?php

namespace App\Services\Enterprise\Workflow\Designer;

use App\Models\Organization;
use App\Models\WorkflowCanvasSnapshot;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowVariable;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Data\WorkflowDefinitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowNodeData;
use App\Modules\Sdk\Workflow\Data\WorkflowTransitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowTriggerData;
use App\Modules\Sdk\Workflow\Data\WorkflowVariableData;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvas;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowExportResult;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowImportResult;
use App\Modules\Sdk\Workflow\Designer\Enums\WorkflowCanvasStatus;
use App\Modules\Sdk\Workflow\Designer\Enums\WorkflowExportFormat;
use App\Modules\Sdk\Workflow\Designer\Enums\WorkflowImportFormat;
use App\Modules\Sdk\Workflow\Designer\Exceptions\WorkflowImportException;
use App\Modules\Sdk\Workflow\Enums\WorkflowStatus;
use App\Services\Enterprise\Workflow\WorkflowVersionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkflowImportExportService
{
    public function __construct(
        private readonly WorkflowVersionService $versionService,
        private readonly WorkflowDesignerAuditRecorder $auditRecorder,
        private readonly WorkflowDesignerSearchIndexer $searchIndexer,
    ) {
    }

    public function export(EnterpriseScope $scope, WorkflowDefinition $definition): WorkflowExportResult
    {
        $draftVersion = $definition->versions()
            ->where('status', 'draft')
            ->orderByDesc('version_number')
            ->first();

        $version = $draftVersion ?? $definition->currentVersion;
        $definitionJson = $version?->definition_json ?? [];

        $latestSnapshot = WorkflowCanvasSnapshot::query()
            ->where('workflow_definition_id', $definition->id)
            ->orderByDesc('created_at')
            ->first();

        $canvas = $latestSnapshot !== null
            ? WorkflowCanvas::fromArray($latestSnapshot->canvas_json)
            : null;

        $variables = $definition->variables->map(fn ($v) => [
            'key' => $v->variable_key,
            'label' => $v->label,
            'type' => $v->type,
            'default_value' => $v->default_value,
            'is_required' => $v->is_required,
            'metadata' => $v->metadata ?? [],
        ])->all();

        $payload = [
            'format' => WorkflowExportFormat::HeosJson->value,
            'version' => '1.0',
            'workflow' => [
                'workflow_key' => $definition->workflow_key,
                'name' => $definition->name,
                'description' => $definition->description,
                'module_key' => $definition->module_key,
                'metadata' => $definition->metadata ?? [],
                'definition' => $definitionJson,
                'variables' => $variables,
            ],
            'canvas' => $canvas?->toArray(),
        ];

        $this->auditRecorder->recordWorkflowExported($definition);

        return new WorkflowExportResult(
            workflowDefinitionPublicId: $definition->public_id,
            workflowKey: $definition->workflow_key,
            format: WorkflowExportFormat::HeosJson->value,
            payload: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function import(
        EnterpriseScope $scope,
        array $payload,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowImportResult {
        $format = (string) ($payload['format'] ?? WorkflowImportFormat::HeosJson->value);

        if ($format !== WorkflowImportFormat::HeosJson->value) {
            throw new WorkflowImportException(sprintf('Unsupported import format [%s].', $format));
        }

        $workflowPayload = is_array($payload['workflow'] ?? null) ? $payload['workflow'] : $payload;

        if ($workflowPayload === []) {
            throw new WorkflowImportException('Import payload is missing workflow data.');
        }

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

        $baseKey = (string) ($workflowPayload['workflow_key'] ?? 'imported_'.Str::lower(Str::random(8)));
        $workflowKey = $this->resolveUniqueKey(
            $organization->id,
            $workspaceId,
            $workflowPayload['module_key'] ?? $scope->moduleKey,
            $baseKey,
        );

        return DB::transaction(function () use (
            $scope,
            $workflowPayload,
            $payload,
            $organization,
            $workspaceId,
            $workflowKey,
            $userId,
            $membershipId,
        ) {
            $definitionJson = is_array($workflowPayload['definition'] ?? null)
                ? $workflowPayload['definition']
                : $this->extractDefinitionJson($workflowPayload);

            $definition = WorkflowDefinition::query()->create([
                'organization_id' => $organization->id,
                'workspace_id' => $workspaceId,
                'module_key' => $workflowPayload['module_key'] ?? $scope->moduleKey,
                'workflow_key' => $workflowKey,
                'name' => (string) ($workflowPayload['name'] ?? 'Imported Workflow'),
                'description' => $workflowPayload['description'] ?? null,
                'status' => WorkflowStatus::Draft,
                'metadata' => is_array($workflowPayload['metadata'] ?? null) ? $workflowPayload['metadata'] : [],
                'created_by_user_id' => $userId,
                'created_membership_id' => $membershipId,
            ]);

            $version = $this->versionService->createDraft($definition, $definitionJson, $userId, $membershipId);
            $this->importVariables($definition, $workflowPayload);
            $snapshotPublicId = $this->importCanvas($definition, $payload, $organization->id, $workspaceId, $version, $userId, $membershipId);

            $this->auditRecorder->recordWorkflowImported($definition);
            $this->searchIndexer->indexDefinitionBestEffort($definition);

            return new WorkflowImportResult(
                workflowDefinitionPublicId: $definition->public_id,
                workflowKey: $definition->workflow_key,
                name: $definition->name,
                format: WorkflowImportFormat::HeosJson->value,
                status: WorkflowStatus::Draft->value,
                snapshotPublicId: $snapshotPublicId,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $workflowPayload
     */
    private function importVariables(WorkflowDefinition $definition, array $workflowPayload): void
    {
        $variables = is_array($workflowPayload['variables'] ?? null) ? $workflowPayload['variables'] : [];

        foreach ($variables as $variable) {
            if (! is_array($variable)) {
                continue;
            }

            WorkflowVariable::query()->create([
                'workflow_definition_id' => $definition->id,
                'variable_key' => (string) ($variable['key'] ?? $variable['variable_key'] ?? ''),
                'label' => $variable['label'] ?? null,
                'type' => (string) ($variable['type'] ?? 'string'),
                'default_value' => $variable['default_value'] ?? null,
                'is_required' => (bool) ($variable['is_required'] ?? false),
                'metadata' => is_array($variable['metadata'] ?? null) ? $variable['metadata'] : [],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function importCanvas(
        WorkflowDefinition $definition,
        array $payload,
        string $organizationId,
        ?string $workspaceId,
        \App\Models\WorkflowVersion $version,
        ?string $userId,
        ?string $membershipId,
    ): ?string {
        if (! is_array($payload['canvas'] ?? null)) {
            return null;
        }

        $snapshot = WorkflowCanvasSnapshot::query()->create([
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'workflow_definition_id' => $definition->id,
            'workflow_version_id' => $version->id,
            'canvas_json' => $payload['canvas'],
            'status' => WorkflowCanvasStatus::Saved,
            'metadata' => ['imported' => true],
            'created_by_user_id' => $userId,
            'created_by_membership_id' => $membershipId,
        ]);

        return $snapshot->public_id;
    }

    /**
     * @param  array<string, mixed>  $workflowPayload
     * @return array<string, mixed>
     */
    private function extractDefinitionJson(array $workflowPayload): array
    {
        return [
            'nodes' => is_array($workflowPayload['nodes'] ?? null) ? $workflowPayload['nodes'] : [],
            'transitions' => is_array($workflowPayload['transitions'] ?? null) ? $workflowPayload['transitions'] : [],
            'triggers' => is_array($workflowPayload['triggers'] ?? null) ? $workflowPayload['triggers'] : [],
            'variables' => is_array($workflowPayload['variables'] ?? null) ? $workflowPayload['variables'] : [],
        ];
    }

    private function resolveUniqueKey(
        string $organizationId,
        ?string $workspaceId,
        ?string $moduleKey,
        string $baseKey,
    ): string {
        $key = $baseKey;
        $attempt = 0;

        while (WorkflowDefinition::query()
            ->where('organization_id', $organizationId)
            ->where('workspace_id', $workspaceId)
            ->where('module_key', $moduleKey)
            ->where('workflow_key', $key)
            ->whereNull('deleted_at')
            ->exists()) {
            $attempt++;
            $key = $baseKey.'_'.$attempt;
        }

        return $key;
    }
}
