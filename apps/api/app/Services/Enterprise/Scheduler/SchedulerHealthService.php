<?php

namespace App\Services\Enterprise\Scheduler;

use App\Enums\ScheduledTaskStatus;
use App\Models\ScheduledTask;
use App\Support\Tenant\TenantContext;

class SchedulerHealthService
{
    public function __construct(
        private readonly ScheduleExpressionHelper $scheduleHelper,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.scheduler.enabled', true);
        $warnings = [];

        if (! $enabled) {
            $warnings[] = 'Enterprise scheduler is disabled in configuration.';
        }

        $query = ScheduledTask::query()->whereNull('deleted_at');

        if ($context !== null) {
            $query->where('organization_id', $context->organization->id);
        }

        $activeCount = (clone $query)
            ->where('status', ScheduledTaskStatus::Active->value)
            ->where('enabled', true)
            ->count();

        $pausedCount = (clone $query)
            ->where('status', ScheduledTaskStatus::Paused->value)
            ->count();

        $dueCount = 0;

        (clone $query)
            ->where('enabled', true)
            ->where('status', ScheduledTaskStatus::Active->value)
            ->chunkById(100, function ($tasks) use (&$dueCount) {
                foreach ($tasks as $task) {
                    if ($this->scheduleHelper->isDue($task)) {
                        $dueCount++;
                    }
                }
            });

        return [
            'enabled' => $enabled,
            'active_count' => $activeCount,
            'paused_count' => $pausedCount,
            'due_count' => $dueCount,
            'warnings' => $warnings,
            'status' => $warnings === [] ? 'healthy' : 'warning',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function runtimeContribution(?TenantContext $context = null): array
    {
        $assessment = $this->assess($context);

        return [
            'enabled' => $assessment['enabled'],
            'due_count' => $assessment['due_count'],
            'active_count' => $assessment['active_count'],
            'paused_count' => $assessment['paused_count'],
        ];
    }
}
