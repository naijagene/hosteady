<?php

namespace App\Services\Enterprise\Workflow\Runtime;

use App\Models\Organization;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Models\WorkflowVersion;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Enums\WorkflowStatus;
use App\Modules\Sdk\Workflow\Enums\WorkflowVersionStatus;
use App\Modules\Sdk\Workflow\Runtime\Contracts\WorkflowRuntimePort;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionContext;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionReference;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionResult;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionStatistics;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowInstanceReference;
use App\Modules\Sdk\Workflow\Runtime\Enums\WorkflowInstanceStatus;
use App\Modules\Sdk\Workflow\Runtime\Exceptions\WorkflowExecutionException;
use App\Modules\Sdk\Workflow\Runtime\Exceptions\WorkflowInstanceNotFoundException;
use Illuminate\Support\Facades\DB;

class LaravelWorkflowRuntimeAdapter implements WorkflowRuntimePort
{
    public function __construct(
        private readonly WorkflowExecutionEngine $engine,
        private readonly WorkflowExecutionVariableResolver $variableResolver,
        private readonly WorkflowExecutionStatisticsService $statisticsService,
        private readonly WorkflowExecutionAuditRecorder $auditRecorder,
    ) {
    }

    public function execute(
        EnterpriseScope $scope,
        string $definitionPublicId,
        WorkflowExecutionContext $context,
        ?array $inputPayload = null,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowExecutionResult {
        $definition = $this->findPublishedDefinition($scope, $definitionPublicId);
        $version = $this->publishedVersion($definition);

        return DB::transaction(function () use ($scope, $definition, $version, $context, $inputPayload, $userId, $membershipId) {
            $instance = WorkflowInstance::query()->create([
                'organization_id' => $definition->organization_id,
                'workspace_id' => $definition->workspace_id,
                'workflow_definition_id' => $definition->id,
                'workflow_version_id' => $version->id,
                'status' => WorkflowInstanceStatus::Pending,
                'input_payload' => $inputPayload,
                'metadata' => ['workflow_key' => $definition->workflow_key],
                'started_at' => now(),
                'created_by_user_id' => $userId,
                'created_membership_id' => $membershipId,
            ]);

            $variables = $this->variableResolver->resolve($definition, $context, $inputPayload);
            $snapshots = $this->variableResolver->snapshot($instance, $variables);

            $this->auditRecorder->recordStarted($instance->fresh('definition'));

            $steps = $this->engine->run(
                $instance->fresh('definition'),
                $version->definition_json ?? [],
                $context,
                $variables,
            );

            return new WorkflowExecutionResult(
                instance: $this->toInstanceReference($instance->fresh(['definition', 'version'])),
                steps: $steps,
                variables: $snapshots,
            );
        });
    }

    public function listInstances(EnterpriseScope $scope, ?string $status = null, int $limit = 50): array
    {
        $organizationId = $this->organizationId($scope);
        $workspaceId = $this->workspaceId($scope, $organizationId);

        $query = WorkflowInstance::query()
            ->with(['definition', 'version'])
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at');

        $this->applyWorkspaceScope($query, $workspaceId);

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('created_at')->limit($limit)->get()
            ->map(fn (WorkflowInstance $instance) => $this->toInstanceReference($instance))
            ->all();
    }

    public function getInstance(EnterpriseScope $scope, string $publicId): WorkflowInstanceReference
    {
        return $this->toInstanceReference($this->findInstance($scope, $publicId));
    }

    public function cancel(
        EnterpriseScope $scope,
        string $publicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowInstanceReference {
        $instance = $this->findInstance($scope, $publicId);

        if (in_array($instance->status, [WorkflowInstanceStatus::Completed, WorkflowInstanceStatus::Cancelled], true)) {
            throw new WorkflowExecutionException('Workflow instance cannot be cancelled in its current state.');
        }

        $instance->update([
            'status' => WorkflowInstanceStatus::Cancelled,
            'completed_at' => now(),
            'duration_ms' => $instance->started_at !== null ? (int) $instance->started_at->diffInMilliseconds(now()) : null,
        ]);

        $this->auditRecorder->recordCancelled($instance->fresh('definition'));

        return $this->toInstanceReference($instance->fresh(['definition', 'version']));
    }

    public function resume(
        EnterpriseScope $scope,
        string $publicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowExecutionResult {
        $instance = $this->findInstance($scope, $publicId);

        if ($instance->status !== WorkflowInstanceStatus::Waiting) {
            throw new WorkflowExecutionException('Only waiting workflow instances can be resumed.');
        }

        $variables = $this->variableResolver->loadSnapshot($instance);
        $context = new WorkflowExecutionContext(
            organizationPublicId: $scope->organizationPublicId,
            workspacePublicId: $scope->workspacePublicId,
        );

        $startNodeId = $this->nextNodeId($instance) ?? $instance->current_node_id;
        $instance->update(['status' => WorkflowInstanceStatus::Running, 'current_node_id' => $startNodeId]);

        $this->auditRecorder->recordResumed($instance);

        $steps = $this->engine->run(
            $instance->fresh(['definition', 'version']),
            $instance->version->definition_json ?? [],
            $context,
            $variables,
            $startNodeId,
        );

        return new WorkflowExecutionResult(
            instance: $this->toInstanceReference($instance->fresh(['definition', 'version'])),
            steps: $steps,
            variables: [],
        );
    }

    public function history(EnterpriseScope $scope, string $publicId): array
    {
        $instance = $this->findInstance($scope, $publicId);

        $steps = $instance->steps()->orderBy('started_at')->get()
            ->map(fn ($step) => new WorkflowExecutionReference(
                publicId: $step->public_id,
                nodeId: $step->node_id,
                nodeType: $step->node_type,
                status: $step->status->value,
                startedAt: $step->started_at?->toIso8601String(),
                completedAt: $step->completed_at?->toIso8601String(),
                durationMs: $step->duration_ms,
                result: $step->result,
                warnings: $step->warnings ?? [],
                errors: $step->errors ?? [],
            ))
            ->all();

        $logs = $instance->logs()->orderBy('created_at')->get()
            ->map(fn ($log) => [
                'public_id' => $log->public_id,
                'level' => $log->level,
                'message' => $log->message,
                'context' => $log->context ?? [],
                'created_at' => $log->created_at?->toIso8601String(),
            ])
            ->all();

        return ['steps' => $steps, 'logs' => $logs];
    }

    public function statistics(EnterpriseScope $scope): WorkflowExecutionStatistics
    {
        return $this->statisticsService->statistics(
            $scope,
            $this->organizationId($scope),
            $this->workspaceId($scope, $this->organizationId($scope)),
        );
    }

    private function findPublishedDefinition(EnterpriseScope $scope, string $publicId): WorkflowDefinition
    {
        $definition = WorkflowDefinition::query()
            ->with(['currentVersion', 'variables'])
            ->where('public_id', $publicId)
            ->where('organization_id', $this->organizationId($scope))
            ->where('status', WorkflowStatus::Published)
            ->whereNull('deleted_at')
            ->first();

        if ($definition === null) {
            throw new WorkflowExecutionException('Published workflow definition was not found.');
        }

        return $definition;
    }

    private function publishedVersion(WorkflowDefinition $definition): WorkflowVersion
    {
        $version = $definition->currentVersion;

        if ($version === null || $version->status !== WorkflowVersionStatus::Published) {
            throw new WorkflowExecutionException('Published workflow version was not found.');
        }

        return $version;
    }

    private function findInstance(EnterpriseScope $scope, string $publicId): WorkflowInstance
    {
        $organizationId = $this->organizationId($scope);
        $workspaceId = $this->workspaceId($scope, $organizationId);

        $query = WorkflowInstance::query()
            ->with(['definition', 'version'])
            ->where('public_id', $publicId)
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at');

        $this->applyWorkspaceScope($query, $workspaceId);

        $instance = $query->first();

        if ($instance === null) {
            throw new WorkflowInstanceNotFoundException(sprintf('Workflow instance [%s] was not found.', $publicId));
        }

        return $instance;
    }

    private function nextNodeId(WorkflowInstance $instance): ?string
    {
        $transitions = $instance->version->definition_json['transitions'] ?? [];

        foreach ($transitions as $transition) {
            if (($transition['from'] ?? null) === $instance->current_node_id) {
                return isset($transition['to']) ? (string) $transition['to'] : null;
            }
        }

        return null;
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

    private function toInstanceReference(WorkflowInstance $instance): WorkflowInstanceReference
    {
        return new WorkflowInstanceReference(
            publicId: $instance->public_id,
            status: $instance->status->value,
            definitionPublicId: $instance->definition->public_id,
            definitionName: $instance->definition->name,
            workflowKey: $instance->definition->workflow_key,
            versionPublicId: $instance->version->public_id,
            currentNodeId: $instance->current_node_id,
            inputPayload: $instance->input_payload,
            result: $instance->result,
            warnings: $instance->warnings ?? [],
            errors: $instance->errors ?? [],
            metadata: $instance->metadata ?? [],
            startedAt: $instance->started_at?->toIso8601String(),
            completedAt: $instance->completed_at?->toIso8601String(),
            durationMs: $instance->duration_ms,
            createdAt: $instance->created_at?->toIso8601String(),
        );
    }
}
