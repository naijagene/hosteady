<?php

namespace App\Services\Enterprise\Scheduler;

use App\Enums\ScheduledTaskRunStatus;
use App\Enums\ScheduledTaskStatus;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\ScheduledTask;
use App\Models\ScheduledTaskRun;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Contracts\SchedulerPort;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\PlatformJobDispatchRequest;
use App\Modules\Sdk\Enterprise\Data\ScheduledTaskReference;
use App\Modules\Sdk\Enterprise\Data\ScheduledTaskRequest;
use App\Modules\Sdk\Enterprise\Data\ScheduledTaskRunReference;
use App\Modules\Sdk\Enterprise\Contracts\PlatformJobPort;
use App\Services\Enterprise\Audit\EnterpriseSchedulerAuditRecorder;
use Carbon\Carbon;

class LaravelSchedulerAdapter implements SchedulerPort
{
    public function __construct(
        private readonly ScheduleExpressionHelper $scheduleHelper,
        private readonly EnterpriseSchedulerAuditRecorder $auditRecorder,
    ) {
    }

    public function create(ScheduledTaskRequest $request): ScheduledTaskReference
    {
        $organization = Organization::query()
            ->where('public_id', $request->scope->organizationPublicId)
            ->firstOrFail();

        $workspaceId = null;

        if ($request->scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $request->scope->workspacePublicId)
                ->where('organization_id', $organization->id)
                ->value('id');
        }

        $membership = $this->resolveMembership($organization->id, $request->createdMembershipPublicId);
        $runAt = $request->runAt !== null ? Carbon::parse($request->runAt, $request->timezone ?? 'UTC') : null;
        $nextRunAt = $this->scheduleHelper->calculateNextRun(
            $request->cronExpression,
            $runAt,
            $request->timezone,
        );

        $task = ScheduledTask::query()->create([
            'organization_id' => $organization->id,
            'workspace_id' => $workspaceId,
            'module_key' => $request->scope->moduleKey,
            'task_type' => $request->taskType,
            'display_name' => $request->displayName,
            'description' => $request->description,
            'cron_expression' => $request->cronExpression,
            'run_at' => $runAt,
            'timezone' => $request->timezone ?? 'UTC',
            'payload' => $request->payload,
            'entity_reference' => $request->entityReference?->toArray(),
            'status' => ScheduledTaskStatus::Active,
            'enabled' => $request->enabled,
            'next_run_at' => $nextRunAt,
            'created_by_user_id' => $membership->user_id,
            'created_membership_id' => $membership->id,
        ]);

        $this->auditRecorder->recordCreated($task);

        return $this->toReference($task);
    }

    public function pause(EnterpriseScope $scope, string $taskPublicId): ScheduledTaskReference
    {
        $task = $this->findModel($scope, $taskPublicId);
        $task->status = ScheduledTaskStatus::Paused;
        $task->enabled = false;
        $task->save();

        $this->auditRecorder->recordPaused($task);

        return $this->toReference($task);
    }

    public function resume(EnterpriseScope $scope, string $taskPublicId): ScheduledTaskReference
    {
        $task = $this->findModel($scope, $taskPublicId);
        $task->status = ScheduledTaskStatus::Active;
        $task->enabled = true;
        $task->next_run_at = $this->scheduleHelper->calculateNextRun(
            $task->cron_expression,
            $task->run_at,
            $task->timezone,
        );
        $task->save();

        $this->auditRecorder->recordResumed($task);

        return $this->toReference($task);
    }

    public function cancel(EnterpriseScope $scope, string $taskPublicId): void
    {
        $task = $this->findModel($scope, $taskPublicId);
        $task->status = ScheduledTaskStatus::Cancelled;
        $task->enabled = false;
        $task->save();

        $this->auditRecorder->recordCancelled($task);
    }

    public function find(EnterpriseScope $scope, string $taskPublicId): ?ScheduledTaskReference
    {
        $task = ScheduledTask::query()
            ->where('public_id', $taskPublicId)
            ->where('organization_id', $this->organizationId($scope))
            ->whereNull('deleted_at')
            ->first();

        return $task !== null ? $this->toReference($task) : null;
    }

    /**
     * @return list<ScheduledTaskReference>
     */
    public function list(EnterpriseScope $scope, int $limit = 50): array
    {
        $query = ScheduledTask::query()
            ->where('organization_id', $this->organizationId($scope))
            ->whereNull('deleted_at');

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $this->organizationId($scope))
                ->value('id');

            $query->where(function ($builder) use ($workspaceId) {
                $builder->whereNull('workspace_id')
                    ->orWhere('workspace_id', $workspaceId);
            });
        }

        return $query
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (ScheduledTask $task) => $this->toReference($task))
            ->all();
    }

    /**
     * @return list<ScheduledTaskRunReference>
     */
    public function listRuns(EnterpriseScope $scope, string $taskPublicId, int $limit = 50): array
    {
        $task = $this->findModel($scope, $taskPublicId);

        return ScheduledTaskRun::query()
            ->where('scheduled_task_id', $task->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (ScheduledTaskRun $run) => $this->toRunReference($run, $task))
            ->all();
    }

    public function findModel(EnterpriseScope $scope, string $taskPublicId): ScheduledTask
    {
        return ScheduledTask::query()
            ->where('public_id', $taskPublicId)
            ->where('organization_id', $this->organizationId($scope))
            ->whereNull('deleted_at')
            ->firstOrFail();
    }

    private function organizationId(EnterpriseScope $scope): string
    {
        return (string) Organization::query()
            ->where('public_id', $scope->organizationPublicId)
            ->value('id');
    }

    private function resolveMembership(string $organizationId, ?string $membershipPublicId): OrganizationMembership
    {
        $query = OrganizationMembership::query()->where('organization_id', $organizationId);

        if ($membershipPublicId !== null) {
            $query->where('public_id', $membershipPublicId);
        }

        return $query->firstOrFail();
    }

    private function toReference(ScheduledTask $task): ScheduledTaskReference
    {
        $entityReference = null;

        if (is_array($task->entity_reference) && isset($task->entity_reference['type'])) {
            $entityReference = EntityReference::fromArray($task->entity_reference);
        }

        return new ScheduledTaskReference(
            publicId: $task->public_id,
            taskType: $task->task_type,
            status: $task->status->value,
            displayName: $task->display_name,
            enabled: $task->enabled,
            description: $task->description,
            moduleKey: $task->module_key,
            entityReference: $entityReference,
            cronExpression: $task->cron_expression,
            runAt: $task->run_at?->toIso8601String(),
            timezone: $task->timezone,
            payload: $task->payload ?? [],
            lastRunAt: $task->last_run_at?->toIso8601String(),
            nextRunAt: $task->next_run_at?->toIso8601String(),
            createdAt: $task->created_at?->toIso8601String(),
        );
    }

    private function toRunReference(ScheduledTaskRun $run, ScheduledTask $task): ScheduledTaskRunReference
    {
        return new ScheduledTaskRunReference(
            publicId: $run->public_id,
            status: $run->status->value,
            scheduledTaskPublicId: $task->public_id,
            platformJobPublicId: $run->platformJob?->public_id,
            errorMessage: $run->error_message,
            output: $run->output,
            startedAt: $run->started_at?->toIso8601String(),
            finishedAt: $run->finished_at?->toIso8601String(),
            createdAt: $run->created_at?->toIso8601String(),
        );
    }
}
