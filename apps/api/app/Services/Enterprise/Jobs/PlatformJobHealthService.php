<?php

namespace App\Services\Enterprise\Jobs;

use App\Enums\PlatformJobStatus;
use App\Models\PlatformJob;
use App\Support\Tenant\TenantContext;

class PlatformJobHealthService
{
    /**
     * @return array<string, mixed>
     */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.jobs.enabled', true);
        $queueDriver = (string) config('queue.default', 'sync');
        $warnings = [];

        if (! $enabled) {
            $warnings[] = 'Enterprise jobs are disabled in configuration.';
        }

        $query = PlatformJob::query()->whereNull('deleted_at');

        if ($context !== null) {
            $query->where('organization_id', $context->organization->id);
        }

        $pendingCount = (clone $query)->whereIn('status', [
            PlatformJobStatus::Pending->value,
            PlatformJobStatus::Queued->value,
        ])->count();

        $runningCount = (clone $query)->where('status', PlatformJobStatus::Running->value)->count();
        $failedCount = (clone $query)->where('status', PlatformJobStatus::Failed->value)->count();

        return [
            'enabled' => $enabled,
            'queue_driver' => $queueDriver,
            'default_queue' => (string) config('heos.enterprise.jobs.default_queue', 'default'),
            'pending_count' => $pendingCount,
            'running_count' => $runningCount,
            'failed_count' => $failedCount,
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
            'pending_count' => $assessment['pending_count'],
            'running_count' => $assessment['running_count'],
            'failed_count' => $assessment['failed_count'],
            'queue_driver' => $assessment['queue_driver'],
            'default_queue' => $assessment['default_queue'],
        ];
    }
}
