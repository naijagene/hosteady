<?php

namespace App\Services\Enterprise\Scheduler;

use App\Enums\ScheduledTaskStatus;
use App\Models\ScheduledTask;
use Carbon\Carbon;

class ScheduleExpressionHelper
{
    public function calculateNextRun(
        ?string $cronExpression,
        ?Carbon $runAt,
        ?string $timezone,
        ?Carbon $from = null,
    ): ?Carbon {
        $from ??= now('UTC');

        if ($runAt !== null) {
            return $runAt->copy()->timezone('UTC');
        }

        $base = $from->copy()->timezone($timezone ?? 'UTC');

        return match ($cronExpression) {
            '@every_minute' => $base->copy()->addMinute(),
            '@hourly' => $base->copy()->addHour(),
            '@daily' => $base->copy()->addDay(),
            default => null,
        };
    }

    public function isDue(ScheduledTask $task, ?Carbon $now = null): bool
    {
        $now ??= now('UTC');

        if (! $task->enabled || $task->status !== ScheduledTaskStatus::Active) {
            return false;
        }

        if ($task->next_run_at !== null && $task->next_run_at->lte($now)) {
            return true;
        }

        if ($task->run_at !== null && $task->last_run_at === null && $task->run_at->lte($now)) {
            return true;
        }

        return false;
    }

    public function isOneTime(ScheduledTask $task): bool
    {
        return $task->run_at !== null && $task->cron_expression === null;
    }
}
