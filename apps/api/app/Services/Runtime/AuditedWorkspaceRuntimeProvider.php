<?php

namespace App\Services\Runtime;

use App\Exceptions\WorkspaceApplication\RuntimeUnavailableException;
use App\Exceptions\WorkspaceApplication\WorkspaceApplicationException;
use App\Services\Audit\DomainAuditRecorder;
use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeContext;
use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeSummary;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;

class AuditedWorkspaceRuntimeProvider implements WorkspaceRuntimeProvider
{
    public function __construct(
        private readonly WorkspaceRuntimeProvider $inner,
        private readonly DomainAuditRecorder $domainAuditRecorder,
        private readonly RuntimeMetricsCollector $metricsCollector,
    ) {
    }

    public function resolve(TenantContext $context, ?string $activeWorkspaceApplicationPublicId = null): WorkspaceRuntimeContext
    {
        $startedAt = hrtime(true);

        try {
            $runtime = $this->inner->resolve($context, $activeWorkspaceApplicationPublicId);
            $this->recordSuccess($context, $runtime, $startedAt);

            return $runtime;
        } catch (WorkspaceApplicationException $exception) {
            throw $exception;
        } catch (\Throwable) {
            try {
                $this->domainAuditRecorder->recordWorkspaceRuntimeFailed($context);
            } catch (\Throwable) {
                // Audit is best effort and must never interrupt runtime generation.
            }

            throw new RuntimeUnavailableException;
        }
    }

    public function resolveSummary(TenantContext $context): WorkspaceRuntimeSummary
    {
        try {
            return $this->inner->resolveSummary($context);
        } catch (WorkspaceApplicationException $exception) {
            throw $exception;
        } catch (\Throwable) {
            try {
                $this->domainAuditRecorder->recordWorkspaceRuntimeFailed($context);
            } catch (\Throwable) {
                // Audit is best effort and must never interrupt runtime generation.
            }

            throw new RuntimeUnavailableException;
        }
    }

    private function recordSuccess(
        TenantContext $context,
        WorkspaceRuntimeContext $runtime,
        int $startedAt,
    ): void {
        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        $settingCount = 0;

        foreach ($runtime->activeApplications as $application) {
            $settingCount += count($application->settings);
        }

        $this->metricsCollector->record(
            generationDurationMs: round($durationMs, 3),
            applicationCount: count($runtime->activeApplications),
            settingCount: $settingCount,
            cacheHitPossible: $this->metricsCollector->lastCacheHitPossible(),
        );

        try {
            $this->domainAuditRecorder->recordWorkspaceRuntimeGenerated($context, $runtime);
        } catch (\Throwable) {
            // Audit is best effort and must never interrupt runtime generation.
        }
    }
}
