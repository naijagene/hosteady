<?php

namespace App\Services\Runtime;

use App\Services\Runtime\Data\RuntimeCacheDiagnostics;
use App\Services\Runtime\Data\RuntimePerformanceMetrics;
use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeSummary;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Facades\Cache;

class RuntimeCacheDiagnosticsService
{
    public function __construct(
        private readonly RuntimeCacheStore $cacheStore,
        private readonly RuntimeCacheKeyBuilder $cacheKeyBuilder,
        private readonly RuntimeMetricsCollector $metricsCollector,
    ) {
    }

    public function diagnose(
        TenantContext $context,
        WorkspaceRuntimeSummary $summary,
        ?string $activeWorkspaceApplicationPublicId = null,
    ): RuntimeCacheDiagnostics {
        $enabled = (bool) config('heos.runtime_cache.enabled', true);
        $generation = $this->cacheStore->currentGeneration(
            $context->organizationPublicId,
            $context->workspacePublicId,
        );

        $key = $enabled
            ? $this->cacheKeyBuilder->build($context, $summary, $activeWorkspaceApplicationPublicId)
            : null;

        $hitPossible = false;

        if ($enabled && is_string($key)) {
            $cached = $this->cacheStore->get($key);
            $hitPossible = is_array($cached);
        }

        return new RuntimeCacheDiagnostics(
            enabled: $enabled,
            generation: $generation,
            key: $key,
            ttl: (int) config('heos.runtime_cache.ttl', 300),
            hitPossible: $hitPossible || $this->metricsCollector->lastCacheHitPossible(),
            backend: $this->resolveBackendName(),
            schemaVersion: (int) config('heos.runtime_cache.schema_version', 1),
        );
    }

    public function cacheStatus(RuntimeCacheDiagnostics $cache): string
    {
        if (! $cache->enabled) {
            return 'disabled';
        }

        if ($cache->hitPossible) {
            return 'hit_possible';
        }

        return 'miss';
    }

    public function performanceFromSummary(
        WorkspaceRuntimeSummary $summary,
        RuntimeCacheDiagnostics $cache,
    ): RuntimePerformanceMetrics {
        $lastMetrics = $this->metricsCollector->lastMetrics();

        if ($lastMetrics !== null) {
            return $lastMetrics;
        }

        return $this->metricsCollector->estimate(
            applicationCount: $summary->activeApplicationCount,
            settingCount: 0,
            cacheHitPossible: $cache->hitPossible,
        );
    }

    private function resolveBackendName(): string
    {
        $configuredStore = config('heos.runtime_cache.store');

        if (is_string($configuredStore) && $configuredStore !== '') {
            return $configuredStore;
        }

        return (string) config('cache.default', 'database');
    }
}
