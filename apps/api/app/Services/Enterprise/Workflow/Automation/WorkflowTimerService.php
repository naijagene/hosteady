<?php

namespace App\Services\Enterprise\Workflow\Automation;

use App\Models\WorkflowHumanTask;
use App\Models\WorkflowInstance;
use App\Models\WorkflowTimer;
use App\Models\WorkflowTimerExecution;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Automation\Contracts\WorkflowTimerHandler;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowAutomationResult;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowTimerReference;
use App\Modules\Sdk\Workflow\Automation\Enums\WorkflowTimerStatus;
use App\Modules\Sdk\Workflow\Automation\Enums\WorkflowTriggerExecutionStatus;
use App\Modules\Sdk\Workflow\Automation\Exceptions\WorkflowTimerException;
use App\Modules\Sdk\Workflow\Human\Enums\HumanTaskStatus;
use App\Modules\Sdk\Workflow\Runtime\Contracts\WorkflowRuntimePort;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionContext;
use App\Modules\Sdk\Workflow\Runtime\Enums\WorkflowInstanceStatus;
use Carbon\Carbon;

class WorkflowTimerService implements WorkflowTimerHandler
{
    public function __construct(
        private readonly WorkflowRuntimePort $runtimePort,
        private readonly WorkflowAutomationAuditRecorder $auditRecorder,
    ) {
    }

    /**
     * @param  array<string, mixed>  $node
     */
    public function createFromWaitNode(
        EnterpriseScope $scope,
        string $workflowInstancePublicId,
        array $node,
        WorkflowExecutionContext $context,
    ): WorkflowTimerReference {
        if (! isset($node['timer']) || ! is_array($node['timer'])) {
            throw new WorkflowTimerException('Wait node is missing timer configuration.');
        }

        $instance = WorkflowInstance::query()
            ->with(['organization', 'workspace'])
            ->where('public_id', $workflowInstancePublicId)
            ->firstOrFail();

        $timerConfig = $node['timer'];
        $timerType = (string) ($timerConfig['type'] ?? 'delay');
        $dueAt = $this->resolveDueAt($timerConfig);

        $timer = WorkflowTimer::query()->create([
            'organization_id' => $instance->organization_id,
            'workspace_id' => $instance->workspace_id,
            'workflow_instance_id' => $instance->id,
            'node_id' => (string) ($node['id'] ?? 'unknown'),
            'timer_type' => $timerType,
            'status' => WorkflowTimerStatus::Active,
            'due_at' => $dueAt,
            'metadata' => [
                'timer_config' => $timerConfig,
            ],
        ]);

        $this->auditRecorder->recordTimerCreated($timer);

        return $this->toReference($timer->fresh(['workflowInstance']));
    }

    public function executeDueTimer(string $timerPublicId): WorkflowAutomationResult
    {
        $timer = WorkflowTimer::query()
            ->with(['workflowInstance.organization', 'workflowInstance.workspace'])
            ->where('public_id', $timerPublicId)
            ->firstOrFail();

        if ($timer->status !== WorkflowTimerStatus::Active) {
            throw new WorkflowTimerException('Timer is not active.');
        }

        $execution = WorkflowTimerExecution::query()->create([
            'workflow_timer_id' => $timer->id,
            'status' => WorkflowTriggerExecutionStatus::Running,
            'executed_at' => now(),
        ]);

        try {
            $instance = $timer->workflowInstance;

            if ($instance === null || $instance->status !== WorkflowInstanceStatus::Waiting) {
                throw new WorkflowTimerException('Workflow instance is not waiting.');
            }

            WorkflowHumanTask::query()
                ->where('workflow_instance_id', $instance->id)
                ->where('node_id', $timer->node_id)
                ->whereNotIn('status', [HumanTaskStatus::Completed, HumanTaskStatus::Cancelled])
                ->update([
                    'status' => HumanTaskStatus::Completed,
                    'completed_at' => now(),
                ]);

            $scope = new EnterpriseScope(
                organizationPublicId: $instance->organization->public_id,
                workspacePublicId: $instance->workspace?->public_id,
            );

            $result = $this->runtimePort->resume($scope, $instance->public_id);

            $timer->update(['status' => WorkflowTimerStatus::Executed]);
            $execution->update(['status' => WorkflowTriggerExecutionStatus::Succeeded]);
            $this->auditRecorder->recordTimerExecuted($timer->fresh());

            return new WorkflowAutomationResult(
                status: 'succeeded',
                workflowInstancePublicId: $result->instance->publicId,
                metadata: ['timer_execution_public_id' => $execution->public_id],
            );
        } catch (\Throwable $exception) {
            $timer->update(['status' => WorkflowTimerStatus::Failed]);
            $execution->update([
                'status' => WorkflowTriggerExecutionStatus::Failed,
                'error_message' => $exception->getMessage(),
            ]);
            $this->auditRecorder->recordTimerFailed($timer->fresh(), $exception->getMessage());

            throw new WorkflowTimerException($exception->getMessage(), previous: $exception);
        }
    }

    public function cancelTimer(EnterpriseScope $scope, string $timerPublicId): WorkflowTimerReference
    {
        $timer = WorkflowTimer::query()
            ->with(['workflowInstance', 'organization', 'workspace'])
            ->where('public_id', $timerPublicId)
            ->whereHas('organization', fn ($query) => $query->where('public_id', $scope->organizationPublicId))
            ->firstOrFail();

        $timer->update(['status' => WorkflowTimerStatus::Cancelled]);
        $this->auditRecorder->recordTimerCancelled($timer->fresh(['workflowInstance']));

        return $this->toReference($timer->fresh(['workflowInstance']));
    }

    /**
     * @param  array<string, mixed>  $timerConfig
     */
    private function resolveDueAt(array $timerConfig): Carbon
    {
        $type = (string) ($timerConfig['type'] ?? 'delay');

        if ($type === 'due_at') {
            $dueAt = (string) ($timerConfig['due_at'] ?? '');

            if ($dueAt === '') {
                throw new WorkflowTimerException('Timer due_at value is required.');
            }

            return Carbon::parse($dueAt);
        }

        $delaySeconds = (int) ($timerConfig['delay_seconds'] ?? 0);

        if ($delaySeconds <= 0) {
            throw new WorkflowTimerException('Timer delay_seconds must be greater than zero.');
        }

        return now()->addSeconds($delaySeconds);
    }

    private function toReference(WorkflowTimer $timer): WorkflowTimerReference
    {
        return new WorkflowTimerReference(
            publicId: $timer->public_id,
            timerType: $timer->timer_type,
            status: $timer->status->value,
            nodeId: $timer->node_id,
            workflowInstancePublicId: $timer->workflowInstance->public_id,
            dueAt: $timer->due_at->toIso8601String(),
            metadata: $timer->metadata ?? [],
            createdAt: $timer->created_at?->toIso8601String(),
        );
    }
}
