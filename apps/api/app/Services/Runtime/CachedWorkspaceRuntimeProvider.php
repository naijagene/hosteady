<?php

namespace App\Services\Runtime;

use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeContext;
use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeSummary;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

class CachedWorkspaceRuntimeProvider implements WorkspaceRuntimeProvider
{
    public function __construct(
        private readonly WorkspaceRuntimeProvider $inner,
        private readonly RuntimeCacheStore $cacheStore,
        private readonly RuntimeCacheKeyBuilder $cacheKeyBuilder,
        private readonly RuntimeSnapshotSerializer $snapshotSerializer,
        private readonly RuntimeMetricsCollector $metricsCollector,
    ) {
    }

    public function resolve(TenantContext $context, ?string $activeWorkspaceApplicationPublicId = null): WorkspaceRuntimeContext
    {
        if (! $this->isEnabled()) {
            return $this->inner->resolve($context, $activeWorkspaceApplicationPublicId);
        }

        $summary = $this->inner->resolveSummary($context);
        $cacheKey = $this->cacheKeyBuilder->build($context, $summary, $activeWorkspaceApplicationPublicId);
        $cached = $this->cacheStore->get($cacheKey);

        if (is_array($cached)) {
            $this->metricsCollector->record(
                generationDurationMs: 0.0,
                applicationCount: $summary->activeApplicationCount,
                settingCount: 0,
                cacheHitPossible: true,
            );

            return $this->snapshotSerializer->deserialize($cached);
        }

        return $this->resolveWithStampedeProtection(
            $context,
            $activeWorkspaceApplicationPublicId,
            $cacheKey,
            $summary,
        );
    }

    public function resolveSummary(TenantContext $context): WorkspaceRuntimeSummary
    {
        return $this->inner->resolveSummary($context);
    }

    private function resolveWithStampedeProtection(
        TenantContext $context,
        ?string $activeWorkspaceApplicationPublicId,
        string $cacheKey,
        WorkspaceRuntimeSummary $summary,
    ): WorkspaceRuntimeContext {
        $lockKey = 'heos:runtime:lock:'.hash('sha256', $cacheKey);

        try {
            $lock = Cache::lock($lockKey, (int) config('heos.runtime_cache.lock_seconds', 10));

            if ($lock->get()) {
                try {
                    return $this->resolveCacheMiss($context, $activeWorkspaceApplicationPublicId, $cacheKey, $summary);
                } finally {
                    $lock->release();
                }
            }

            try {
                $lock->block((int) config('heos.runtime_cache.lock_wait_seconds', 5));
                $cached = $this->cacheStore->get($cacheKey);

                if (is_array($cached)) {
                    $this->metricsCollector->record(
                        generationDurationMs: 0.0,
                        applicationCount: $summary->activeApplicationCount,
                        settingCount: 0,
                        cacheHitPossible: true,
                    );

                    return $this->snapshotSerializer->deserialize($cached);
                }
            } catch (LockTimeoutException) {
                // Gracefully fall back to uncached generation.
            }
        } catch (\Throwable) {
            // Lock backend unavailable; fall back without stampede protection.
        }

        return $this->resolveCacheMiss($context, $activeWorkspaceApplicationPublicId, $cacheKey, $summary);
    }

    private function resolveCacheMiss(
        TenantContext $context,
        ?string $activeWorkspaceApplicationPublicId,
        string $cacheKey,
        WorkspaceRuntimeSummary $summary,
    ): WorkspaceRuntimeContext {
        $cached = $this->cacheStore->get($cacheKey);

        if (is_array($cached)) {
            $this->metricsCollector->record(
                generationDurationMs: 0.0,
                applicationCount: $summary->activeApplicationCount,
                settingCount: 0,
                cacheHitPossible: true,
            );

            return $this->snapshotSerializer->deserialize($cached);
        }

        $this->metricsCollector->record(
            generationDurationMs: 0.0,
            applicationCount: $summary->activeApplicationCount,
            settingCount: 0,
            cacheHitPossible: false,
        );

        $runtime = $this->inner->resolve($context, $activeWorkspaceApplicationPublicId);
        $this->cacheStore->put(
            $cacheKey,
            $this->snapshotSerializer->serialize($runtime),
            (int) config('heos.runtime_cache.ttl', 300),
        );

        return $runtime;
    }

    private function isEnabled(): bool
    {
        return (bool) config('heos.runtime_cache.enabled', true);
    }
}
