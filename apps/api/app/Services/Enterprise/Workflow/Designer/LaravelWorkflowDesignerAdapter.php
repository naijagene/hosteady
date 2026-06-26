<?php

namespace App\Services\Enterprise\Workflow\Designer;

use App\Models\Organization;
use App\Models\User;
use App\Models\WorkflowCanvasSnapshot;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowVersion;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Designer\Contracts\WorkflowCanvasNormalizer;
use App\Modules\Sdk\Workflow\Designer\Contracts\WorkflowDesignerPort;
use App\Modules\Sdk\Workflow\Designer\Contracts\WorkflowTemplateProvider;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvas;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCloneResult;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowDesignerDiff;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowDesignerSnapshot;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowExportResult;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowImportResult;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowNodeTemplate;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowPreviewPayload;
use App\Modules\Sdk\Workflow\Designer\Enums\WorkflowCanvasStatus;
use App\Modules\Sdk\Workflow\Designer\Exceptions\WorkflowDesignerException;
use App\Modules\Sdk\Workflow\Enums\WorkflowVersionStatus;
use App\Modules\Sdk\Workflow\Exceptions\WorkflowNotFoundException;
use Illuminate\Support\Facades\DB;

class LaravelWorkflowDesignerAdapter implements WorkflowDesignerPort
{
    public function __construct(
        private readonly WorkflowCanvasNormalizer $normalizer,
        private readonly WorkflowCanvasDiffService $diffService,
        private readonly WorkflowCanvasService $canvasService,
        private readonly WorkflowCloneService $cloneService,
        private readonly WorkflowImportExportService $importExportService,
        private readonly WorkflowTemplateProvider $templateProvider,
        private readonly WorkflowDesignerPreviewService $previewService,
        private readonly WorkflowDesignerHealthService $healthService,
        private readonly WorkflowDesignerAuditRecorder $auditRecorder,
        private readonly WorkflowDesignerSearchIndexer $searchIndexer,
    ) {
    }

    public function getCanvas(EnterpriseScope $scope, string $definitionPublicId): WorkflowDesignerSnapshot
    {
        $definition = $this->findDefinition($scope, $definitionPublicId);
        $snapshot = $this->latestSnapshot($definition);

        if ($snapshot === null) {
            $canvas = $this->buildCanvasFromDefinition($definition);

            return new WorkflowDesignerSnapshot(
                publicId: '',
                workflowDefinitionPublicId: $definition->public_id,
                status: WorkflowCanvasStatus::Draft->value,
                canvas: $canvas,
            );
        }

        return $this->toSnapshotData($snapshot);
    }

    public function saveCanvas(
        EnterpriseScope $scope,
        string $definitionPublicId,
        WorkflowCanvas $canvas,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowDesignerSnapshot {
        $definition = $this->findDefinition($scope, $definitionPublicId);
        $definitionNodes = $this->definitionNodes($definition);

        $normalized = $this->normalizer->normalize($canvas, $definitionNodes);

        return DB::transaction(function () use (
            $definition,
            $normalized,
            $userId,
            $membershipId,
        ) {
            $version = $definition->versions()
                ->where('status', WorkflowVersionStatus::Draft)
                ->orderByDesc('version_number')
                ->first();

            $snapshot = WorkflowCanvasSnapshot::query()->create([
                'organization_id' => $definition->organization_id,
                'workspace_id' => $definition->workspace_id,
                'workflow_definition_id' => $definition->id,
                'workflow_version_id' => $version?->id,
                'canvas_json' => $normalized['canvas']->toArray(),
                'status' => WorkflowCanvasStatus::Saved,
                'metadata' => ['warnings' => $normalized['warnings']],
                'created_by_user_id' => $userId,
                'created_by_membership_id' => $membershipId,
            ]);

            $this->auditRecorder->recordCanvasSaved($snapshot->fresh(['workflowDefinition']));
            $this->auditRecorder->recordSnapshotCreated($snapshot->fresh(['workflowDefinition']));

            if (app()->bound(\App\Support\Tenant\TenantContext::class)) {
                $this->searchIndexer->indexSnapshotBestEffort(
                    app(\App\Support\Tenant\TenantContext::class),
                    $snapshot->fresh(['workflowDefinition']),
                );
            }

            return $this->toSnapshotData($snapshot->fresh(['workflowDefinition', 'workflowVersion']));
        });
    }

    /**
     * @return list<WorkflowDesignerSnapshot>
     */
    public function listSnapshots(EnterpriseScope $scope, string $definitionPublicId, int $limit = 50): array
    {
        $definition = $this->findDefinition($scope, $definitionPublicId);

        return WorkflowCanvasSnapshot::query()
            ->with(['workflowDefinition', 'workflowVersion'])
            ->where('workflow_definition_id', $definition->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (WorkflowCanvasSnapshot $snapshot) => $this->toSnapshotData($snapshot))
            ->all();
    }

    public function getSnapshot(EnterpriseScope $scope, string $snapshotPublicId): WorkflowDesignerSnapshot
    {
        return $this->toSnapshotData($this->findSnapshot($scope, $snapshotPublicId));
    }

    public function diffSnapshots(
        EnterpriseScope $scope,
        string $fromPublicId,
        string $toPublicId,
    ): WorkflowDesignerDiff {
        $from = $this->findSnapshot($scope, $fromPublicId);
        $to = $this->findSnapshot($scope, $toPublicId);

        $diff = $this->diffService->diff(
            $from->public_id,
            $to->public_id,
            WorkflowCanvas::fromArray($from->canvas_json),
            WorkflowCanvas::fromArray($to->canvas_json),
        );

        $this->auditRecorder->recordSnapshotDiffed($from, $to);

        return $diff;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function cloneWorkflow(
        EnterpriseScope $scope,
        string $definitionPublicId,
        array $options = [],
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowCloneResult {
        $definition = $this->findDefinition($scope, $definitionPublicId);

        return $this->cloneService->clone($scope, $definition, $options, $userId, $membershipId);
    }

    public function exportWorkflow(EnterpriseScope $scope, string $definitionPublicId): WorkflowExportResult
    {
        $definition = $this->findDefinition($scope, $definitionPublicId);

        return $this->importExportService->export($scope, $definition);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function importWorkflow(
        EnterpriseScope $scope,
        array $payload,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowImportResult {
        return $this->importExportService->import($scope, $payload, $userId, $membershipId);
    }

    /**
     * @return list<WorkflowNodeTemplate>
     */
    public function listNodeTemplates(EnterpriseScope $scope): array
    {
        return $this->templateProvider->listTemplates($scope);
    }

    public function preview(EnterpriseScope $scope, string $definitionPublicId): WorkflowPreviewPayload
    {
        $definition = $this->findDefinition($scope, $definitionPublicId);

        return $this->previewService->preview($scope, $definition);
    }

    /**
     * @return array{canvases: int, templates: int, snapshots: int}
     */
    public function statistics(EnterpriseScope $scope, ?string $organizationId = null, ?string $workspaceId = null): array
    {
        if ($organizationId === null) {
            $organizationId = Organization::query()
                ->where('public_id', $scope->organizationPublicId)
                ->value('id');
        }

        if ($workspaceId === null && $scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->value('id');
        }

        return $this->healthService->statistics($scope, $organizationId, $workspaceId);
    }

    private function findDefinition(EnterpriseScope $scope, string $publicId): WorkflowDefinition
    {
        $organizationId = Organization::query()
            ->where('public_id', $scope->organizationPublicId)
            ->value('id');

        $workspaceId = null;

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $organizationId)
                ->value('id');
        }

        $query = WorkflowDefinition::query()
            ->with(['currentVersion', 'variables', 'versions'])
            ->where('public_id', $publicId)
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at');

        if ($workspaceId !== null) {
            $query->where(function ($q) use ($workspaceId) {
                $q->where('workspace_id', $workspaceId)->orWhereNull('workspace_id');
            });
        }

        $definition = $query->first();

        if ($definition === null) {
            throw new WorkflowNotFoundException(sprintf('Workflow definition [%s] was not found.', $publicId));
        }

        return $definition;
    }

    private function findSnapshot(EnterpriseScope $scope, string $publicId): WorkflowCanvasSnapshot
    {
        $organizationId = Organization::query()
            ->where('public_id', $scope->organizationPublicId)
            ->value('id');

        $snapshot = WorkflowCanvasSnapshot::query()
            ->with(['workflowDefinition', 'workflowVersion'])
            ->where('public_id', $publicId)
            ->where('organization_id', $organizationId)
            ->first();

        if ($snapshot === null) {
            throw new WorkflowDesignerException(sprintf('Canvas snapshot [%s] was not found.', $publicId));
        }

        return $snapshot;
    }

    private function latestSnapshot(WorkflowDefinition $definition): ?WorkflowCanvasSnapshot
    {
        return WorkflowCanvasSnapshot::query()
            ->with(['workflowDefinition', 'workflowVersion'])
            ->where('workflow_definition_id', $definition->id)
            ->orderByDesc('created_at')
            ->first();
    }

    private function buildCanvasFromDefinition(WorkflowDefinition $definition): WorkflowCanvas
    {
        $draftVersion = $definition->versions()
            ->where('status', WorkflowVersionStatus::Draft)
            ->orderByDesc('version_number')
            ->first();

        $version = $draftVersion ?? $definition->currentVersion;
        $definitionJson = $version?->definition_json ?? [];
        $nodes = [];

        foreach (is_array($definitionJson['nodes'] ?? null) ? $definitionJson['nodes'] : [] as $index => $node) {
            $nodes[] = new \App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvasNode(
                id: (string) $node['id'],
                type: (string) $node['type'],
                label: isset($node['label']) ? (string) $node['label'] : null,
                x: $index * 160,
                y: 100,
            );
        }

        $edges = [];

        foreach (is_array($definitionJson['transitions'] ?? null) ? $definitionJson['transitions'] : [] as $transition) {
            $edges[] = new \App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvasEdge(
                id: (string) ($transition['id'] ?? $transition['from'].'_'.$transition['to']),
                source: (string) $transition['from'],
                target: (string) $transition['to'],
                label: isset($transition['label']) ? (string) $transition['label'] : null,
                condition: isset($transition['condition']) ? (string) $transition['condition'] : null,
            );
        }

        return new WorkflowCanvas(nodes: $nodes, edges: $edges);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function definitionNodes(WorkflowDefinition $definition): array
    {
        $draftVersion = $definition->versions()
            ->where('status', WorkflowVersionStatus::Draft)
            ->orderByDesc('version_number')
            ->first();

        $version = $draftVersion ?? $definition->currentVersion;
        $definitionJson = $version?->definition_json ?? [];

        return is_array($definitionJson['nodes'] ?? null) ? $definitionJson['nodes'] : [];
    }

    private function toSnapshotData(WorkflowCanvasSnapshot $snapshot): WorkflowDesignerSnapshot
    {
        $userPublicId = null;

        if ($snapshot->created_by_user_id !== null) {
            $userPublicId = User::query()->where('id', $snapshot->created_by_user_id)->value('public_id');
        }

        return new WorkflowDesignerSnapshot(
            publicId: $snapshot->public_id,
            workflowDefinitionPublicId: $snapshot->workflowDefinition->public_id,
            status: $snapshot->status->value,
            canvas: WorkflowCanvas::fromArray($snapshot->canvas_json),
            workflowVersionPublicId: $snapshot->workflowVersion?->public_id,
            createdAt: $snapshot->created_at?->toIso8601String(),
            createdByUserPublicId: $userPublicId,
        );
    }
}
