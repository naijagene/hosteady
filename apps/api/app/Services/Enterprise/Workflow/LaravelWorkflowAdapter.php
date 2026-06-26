<?php

namespace App\Services\Enterprise\Workflow;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\WorkflowCategory;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowDefinitionHistory;
use App\Models\WorkflowVariable;
use App\Models\WorkflowVersion;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Contracts\WorkflowPort;
use App\Modules\Sdk\Workflow\Data\WorkflowCategoryReference;
use App\Modules\Sdk\Workflow\Data\WorkflowDefinitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowDefinitionReference;
use App\Modules\Sdk\Workflow\Data\WorkflowPublishResult;
use App\Modules\Sdk\Workflow\Data\WorkflowStatistics;
use App\Modules\Sdk\Workflow\Data\WorkflowValidationReport;
use App\Modules\Sdk\Workflow\Data\WorkflowVersionData;
use App\Modules\Sdk\Workflow\Enums\WorkflowStatus;
use App\Modules\Sdk\Workflow\Enums\WorkflowVersionStatus;
use App\Modules\Sdk\Workflow\Exceptions\DuplicateWorkflowKeyException;
use App\Modules\Sdk\Workflow\Exceptions\WorkflowNotFoundException;
use Illuminate\Support\Facades\DB;

class LaravelWorkflowAdapter implements WorkflowPort
{
    public function __construct(
        private readonly WorkflowValidationService $validationService,
        private readonly WorkflowVersionService $versionService,
        private readonly WorkflowStatisticsService $statisticsService,
        private readonly WorkflowAuditRecorder $auditRecorder,
    ) {
    }

    public function listDefinitions(EnterpriseScope $scope, ?string $status = null): array
    {
        $organizationId = $this->organizationId($scope);
        $workspaceId = $this->workspaceId($scope, $organizationId);

        $query = WorkflowDefinition::query()
            ->with(['currentVersion', 'category'])
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at');

        $this->applyWorkspaceScope($query, $workspaceId);

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->orderBy('name')->get()
            ->map(fn (WorkflowDefinition $definition) => $this->toDefinitionReference($definition))
            ->all();
    }

    public function getDefinition(EnterpriseScope $scope, string $publicId): WorkflowDefinitionReference
    {
        return $this->toDefinitionReference($this->findDefinition($scope, $publicId));
    }

    public function createDefinition(
        EnterpriseScope $scope,
        WorkflowDefinitionData $data,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowDefinitionReference {
        $organizationId = $this->organizationId($scope);
        $workspaceId = $this->workspaceId($scope, $organizationId);

        if ($this->workflowKeyExists($organizationId, $workspaceId, $data->moduleKey, $data->workflowKey)) {
            throw new DuplicateWorkflowKeyException(sprintf('Workflow key [%s] already exists.', $data->workflowKey));
        }

        return DB::transaction(function () use ($scope, $data, $organizationId, $workspaceId, $userId, $membershipId) {
            $definition = WorkflowDefinition::query()->create([
                'organization_id' => $organizationId,
                'workspace_id' => $workspaceId,
                'module_key' => $data->moduleKey ?? $scope->moduleKey,
                'category_id' => $this->resolveCategoryId($scope, $data->categoryPublicId),
                'workflow_key' => $data->workflowKey,
                'name' => $data->name,
                'description' => $data->description,
                'status' => WorkflowStatus::Draft,
                'metadata' => $data->metadata,
                'created_by_user_id' => $userId,
                'created_membership_id' => $membershipId,
            ]);

            $definitionJson = $this->resolveDefinitionJson($data);
            $this->syncVariables($definition, $data->variables);
            $version = $this->versionService->createDraft($definition, $definitionJson, $userId, $membershipId);

            $this->recordHistory($definition, $version, 'created', null, [
                'workflow_key' => $definition->workflow_key,
                'status' => $definition->status->value,
            ], $userId, $membershipId);

            $this->auditRecorder->recordCreated($definition->fresh(['currentVersion', 'category']));
            $report = $this->validationService->validate($data);
            $this->auditRecorder->recordValidated($definition, $report);

            return $this->toDefinitionReference($definition->fresh(['currentVersion', 'category']));
        });
    }

    public function updateDefinition(
        EnterpriseScope $scope,
        string $publicId,
        WorkflowDefinitionData $data,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowDefinitionReference {
        $definition = $this->findDefinition($scope, $publicId);
        $before = ['name' => $definition->name, 'status' => $definition->status->value];

        return DB::transaction(function () use ($scope, $definition, $data, $before, $userId, $membershipId) {
            $definition->update([
                'name' => $data->name,
                'description' => $data->description,
                'module_key' => $data->moduleKey ?? $definition->module_key,
                'category_id' => $this->resolveCategoryId($scope, $data->categoryPublicId),
                'metadata' => $data->metadata,
            ]);

            $definitionJson = $this->resolveDefinitionJson($data);
            $this->syncVariables($definition, $data->variables);

            $draft = $definition->versions()
                ->where('status', WorkflowVersionStatus::Draft)
                ->orderByDesc('version_number')
                ->first();

            if ($draft === null) {
                $draft = $this->versionService->createDraft($definition, $definitionJson, $userId, $membershipId);
            } else {
                $draft->update([
                    'definition_json' => $definitionJson,
                    'validation_report' => $this->validationService
                        ->validateDefinitionJson($definitionJson, $definition->workflow_key)
                        ->toArray(),
                ]);
            }

            $this->recordHistory($definition, $draft, 'updated', $before, [
                'name' => $definition->name,
                'status' => $definition->status->value,
            ], $userId, $membershipId);

            $this->auditRecorder->recordUpdated($definition->fresh(['currentVersion', 'category']));
            $report = $this->validationService->validate($data);
            $this->auditRecorder->recordValidated($definition, $report);

            return $this->toDefinitionReference($definition->fresh(['currentVersion', 'category']));
        });
    }

    public function publishDefinition(
        EnterpriseScope $scope,
        string $publicId,
        ?string $versionPublicId = null,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowPublishResult {
        $definition = $this->findDefinition($scope, $publicId);

        $targetVersion = null;
        if ($versionPublicId !== null) {
            $targetVersion = $definition->versions()->where('public_id', $versionPublicId)->firstOrFail();
        }

        $published = $this->versionService->publish($definition, $targetVersion, $userId, $membershipId);
        $definition = $definition->fresh(['currentVersion', 'category']);
        $report = WorkflowValidationReport::fromArray($published->validation_report ?? ['valid' => true, 'issues' => []]);

        $this->auditRecorder->recordPublished($definition);
        $this->auditRecorder->recordValidated($definition, $report);

        return new WorkflowPublishResult(
            definition: $this->toDefinitionReference($definition),
            publishedVersion: $this->toVersionData($published),
            validationReport: $report,
        );
    }

    public function archiveDefinition(
        EnterpriseScope $scope,
        string $publicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowDefinitionReference {
        $definition = $this->findDefinition($scope, $publicId);
        $archived = $this->versionService->archiveDefinition($definition, $userId, $membershipId);
        $this->auditRecorder->recordArchived($archived);

        return $this->toDefinitionReference($archived);
    }

    public function listVersions(EnterpriseScope $scope, string $definitionPublicId): array
    {
        $definition = $this->findDefinition($scope, $definitionPublicId);

        return $definition->versions()->orderByDesc('version_number')->get()
            ->map(fn (WorkflowVersion $version) => $this->toVersionData($version))
            ->all();
    }

    public function validateDefinition(WorkflowDefinitionData $data): WorkflowValidationReport
    {
        return $this->validationService->validate($data);
    }

    public function listCategories(EnterpriseScope $scope): array
    {
        $organizationId = $this->organizationId($scope);
        $workspaceId = $this->workspaceId($scope, $organizationId);

        $query = WorkflowCategory::query()
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at');

        $this->applyWorkspaceScope($query, $workspaceId);

        return $query->orderBy('name')->get()
            ->map(fn (WorkflowCategory $category) => $this->toCategoryReference($category))
            ->all();
    }

    public function createCategory(
        EnterpriseScope $scope,
        string $categoryKey,
        string $name,
        ?string $description = null,
        ?string $moduleKey = null,
        ?array $metadata = null,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowCategoryReference {
        $organizationId = $this->organizationId($scope);
        $workspaceId = $this->workspaceId($scope, $organizationId);

        $category = WorkflowCategory::query()->create([
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'module_key' => $moduleKey ?? $scope->moduleKey,
            'category_key' => $categoryKey,
            'name' => $name,
            'description' => $description,
            'metadata' => $metadata ?? [],
        ]);

        $this->auditRecorder->recordCategoryCreated($category);

        return $this->toCategoryReference($category);
    }

    public function statistics(EnterpriseScope $scope): WorkflowStatistics
    {
        return $this->statisticsService->statistics(
            $scope,
            $this->organizationId($scope),
            $this->workspaceId($scope, $this->organizationId($scope)),
        );
    }

    private function findDefinition(EnterpriseScope $scope, string $publicId): WorkflowDefinition
    {
        $organizationId = $this->organizationId($scope);
        $workspaceId = $this->workspaceId($scope, $organizationId);

        $query = WorkflowDefinition::query()
            ->with(['currentVersion', 'category'])
            ->where('public_id', $publicId)
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at');

        $this->applyWorkspaceScope($query, $workspaceId);

        $definition = $query->first();

        if ($definition === null) {
            throw new WorkflowNotFoundException(sprintf('Workflow definition [%s] was not found.', $publicId));
        }

        return $definition;
    }

    private function workflowKeyExists(
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
     * @param  list<\App\Modules\Sdk\Workflow\Data\WorkflowVariableData>  $variables
     */
    private function syncVariables(WorkflowDefinition $definition, array $variables): void
    {
        WorkflowVariable::query()
            ->where('workflow_definition_id', $definition->id)
            ->forceDelete();

        foreach ($variables as $variable) {
            WorkflowVariable::query()->create([
                'workflow_definition_id' => $definition->id,
                'variable_key' => $variable->key,
                'label' => $variable->label,
                'type' => $variable->type,
                'default_value' => $variable->defaultValue,
                'is_required' => $variable->isRequired,
                'metadata' => $variable->metadata,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveDefinitionJson(WorkflowDefinitionData $data): array
    {
        if ($data->nodes !== [] || $data->transitions !== [] || $data->triggers !== [] || $data->variables !== []) {
            return $data->toDefinitionJson();
        }

        return $this->defaultDefinitionJson();
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

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function recordHistory(
        WorkflowDefinition $definition,
        ?WorkflowVersion $version,
        string $action,
        ?array $before,
        ?array $after,
        ?string $userId,
        ?string $membershipId,
    ): void {
        WorkflowDefinitionHistory::query()->create([
            'workflow_definition_id' => $definition->id,
            'workflow_version_id' => $version?->id,
            'action' => $action,
            'before_state' => $before,
            'after_state' => $after,
            'created_by_user_id' => $userId,
            'created_membership_id' => $membershipId,
            'created_at' => now(),
        ]);
    }

    private function resolveCategoryId(EnterpriseScope $scope, ?string $categoryPublicId): ?string
    {
        if ($categoryPublicId === null) {
            return null;
        }

        return WorkflowCategory::query()
            ->where('public_id', $categoryPublicId)
            ->where('organization_id', $this->organizationId($scope))
            ->value('id');
    }

    private function organizationId(EnterpriseScope $scope): string
    {
        return (string) Organization::query()
            ->where('public_id', $scope->organizationPublicId)
            ->value('id');
    }

    private function workspaceId(EnterpriseScope $scope, string $organizationId): ?string
    {
        if ($scope->workspacePublicId === null) {
            return null;
        }

        return Workspace::query()
            ->where('public_id', $scope->workspacePublicId)
            ->where('organization_id', $organizationId)
            ->value('id');
    }

    private function applyWorkspaceScope($query, ?string $workspaceId): void
    {
        if ($workspaceId === null) {
            return;
        }

        $query->where(function ($builder) use ($workspaceId) {
            $builder->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
        });
    }

    private function toDefinitionReference(WorkflowDefinition $definition): WorkflowDefinitionReference
    {
        return new WorkflowDefinitionReference(
            publicId: $definition->public_id,
            workflowKey: $definition->workflow_key,
            name: $definition->name,
            status: $definition->status->value,
            description: $definition->description,
            moduleKey: $definition->module_key,
            categoryPublicId: $definition->category?->public_id,
            currentVersionPublicId: $definition->currentVersion?->public_id,
            currentVersion: $definition->currentVersion ? $this->toVersionData($definition->currentVersion) : null,
            metadata: $definition->metadata ?? [],
            createdAt: $definition->created_at?->toIso8601String(),
            updatedAt: $definition->updated_at?->toIso8601String(),
        );
    }

    private function toVersionData(WorkflowVersion $version): WorkflowVersionData
    {
        return new WorkflowVersionData(
            publicId: $version->public_id,
            versionNumber: $version->version_number,
            status: $version->status->value,
            definitionJson: $version->definition_json ?? [],
            validationReport: $version->validation_report,
            publishedAt: $version->published_at?->toIso8601String(),
            archivedAt: $version->archived_at?->toIso8601String(),
            createdAt: $version->created_at?->toIso8601String(),
        );
    }

    private function toCategoryReference(WorkflowCategory $category): WorkflowCategoryReference
    {
        return new WorkflowCategoryReference(
            publicId: $category->public_id,
            categoryKey: $category->category_key,
            name: $category->name,
            description: $category->description,
            moduleKey: $category->module_key,
            metadata: $category->metadata ?? [],
            createdAt: $category->created_at?->toIso8601String(),
        );
    }
}
