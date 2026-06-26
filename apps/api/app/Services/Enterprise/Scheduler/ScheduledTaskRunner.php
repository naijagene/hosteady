<?php

namespace App\Services\Enterprise\Scheduler;

use App\Enums\ScheduledTaskRunStatus;
use App\Enums\ScheduledTaskStatus;
use App\Models\ScheduledTask;
use App\Models\ScheduledTaskRun;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\PlatformJobDispatchRequest;
use App\Modules\Sdk\Enterprise\Contracts\PlatformJobPort;
use App\Services\Enterprise\Audit\EnterpriseSchedulerAuditRecorder;

class ScheduledTaskRunner
{
    public function __construct(
        private readonly ScheduleExpressionHelper $scheduleHelper,
        private readonly PlatformJobPort $platformJobPort,
        private readonly EnterpriseSchedulerAuditRecorder $auditRecorder,
    ) {
    }

    /**
     * @return array{processed: int, skipped: int, failed: int}
     */
    public function runDueTasks(): array
    {
        if (! (bool) config('heos.enterprise.scheduler.enabled', true)) {
            return ['processed' => 0, 'skipped' => 0, 'failed' => 0];
        }

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        ScheduledTask::query()
            ->whereNull('deleted_at')
            ->where('enabled', true)
            ->where('status', ScheduledTaskStatus::Active->value)
            ->orderBy('next_run_at')
            ->chunkById(50, function ($tasks) use (&$processed, &$skipped, &$failed) {
                foreach ($tasks as $task) {
                    if (! $this->scheduleHelper->isDue($task)) {
                        $skipped++;

                        continue;
                    }

                    try {
                        $this->executeTask($task);
                        $processed++;
                    } catch (\Throwable $exception) {
                        $failed++;
                        $this->auditRecorder->recordFailed($task, $exception->getMessage());
                    }
                }
            });

        return compact('processed', 'skipped', 'failed');
    }

    private function executeTask(ScheduledTask $task): void
    {
        $run = ScheduledTaskRun::query()->create([
            'scheduled_task_id' => $task->id,
            'status' => ScheduledTaskRunStatus::Running,
            'started_at' => now(),
        ]);

        $organization = $task->organization;
        $workspace = $task->workspace;

        $scope = new EnterpriseScope(
            organizationPublicId: $organization->public_id,
            workspacePublicId: $workspace?->public_id,
            moduleKey: $task->module_key,
        );

        $entityReference = is_array($task->entity_reference) && isset($task->entity_reference['type'])
            ? \App\Modules\Sdk\Enterprise\Data\EntityReference::fromArray($task->entity_reference)
            : null;

        $membershipPublicId = $task->created_membership_id !== null
            ? (string) \App\Models\OrganizationMembership::query()->where('id', $task->created_membership_id)->value('public_id')
            : null;

        $jobResult = $this->platformJobPort->dispatch(new PlatformJobDispatchRequest(
            scope: $scope,
            jobType: $task->task_type,
            displayName: $task->display_name,
            payload: is_array($task->payload) ? $task->payload : [],
            entityReference: $entityReference,
            scheduledTaskPublicId: $task->public_id,
            createdMembershipPublicId: $membershipPublicId,
        ));

        $platformJob = \App\Models\PlatformJob::query()
            ->where('public_id', $jobResult->job->publicId)
            ->firstOrFail();

        $run->platform_job_id = $platformJob->id;
        $run->status = ScheduledTaskRunStatus::Succeeded;
        $run->finished_at = now();
        $run->output = ['job_public_id' => $jobResult->job->publicId];
        $run->save();

        $task->last_run_at = now();

        if ($this->scheduleHelper->isOneTime($task)) {
            $task->status = ScheduledTaskStatus::Completed;
            $task->enabled = false;
            $task->next_run_at = null;
        } else {
            $task->next_run_at = $this->scheduleHelper->calculateNextRun(
                $task->cron_expression,
                null,
                $task->timezone,
                now(),
            );
        }

        $task->save();

        $this->auditRecorder->recordExecuted($task);
    }
}
