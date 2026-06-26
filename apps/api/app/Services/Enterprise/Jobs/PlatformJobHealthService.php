<?php

namespace App\Services\Enterprise\Jobs;

use App\Enums\PlatformJobStatus;
use App\Models\PlatformJob;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class PlatformJobHealthService
{
    public function __construct(
        private readonly EnterpriseTableHealthGuard $tableGuard,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.jobs.enabled', true);
        $queueDriver = (string) config('queue.default', 'sync');

        return $this->tableGuard->assessWhenTablesPresent(
            ['platform_jobs'],
            fn (): array => $this->assessWithTables($context, $enabled, $queueDriver),
            $this->fallbackAssessment($enabled, $queueDriver),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function assessWithTables(?TenantContext $context, bool $enabled, string $queueDriver): array
    {
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
    private function fallbackAssessment(bool $enabled, string $queueDriver): array
    {
        return [
            'enabled' => $enabled,
            'queue_driver' => $queueDriver,
            'default_queue' => (string) config('heos.enterprise.jobs.default_queue', 'default'),
            'pending_count' => 0,
            'running_count' => 0,
            'failed_count' => 0,
            'warnings' => [],
            'status' => 'healthy',
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
