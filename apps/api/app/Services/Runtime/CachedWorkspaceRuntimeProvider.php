<?php

namespace App\Services\Runtime;

use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeContext;
use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeSummary;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;

class CachedWorkspaceRuntimeProvider implements WorkspaceRuntimeProvider
{
    public function __construct(
        private readonly WorkspaceRuntimeProvider $inner,
        private readonly RuntimeCacheStore $cacheStore,
        private readonly RuntimeCacheKeyBuilder $cacheKeyBuilder,
        private readonly RuntimeSnapshotSerializer $snapshotSerializer,
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
            return $this->snapshotSerializer->deserialize($cached);
        }

        $runtime = $this->inner->resolve($context, $activeWorkspaceApplicationPublicId);
        $this->cacheStore->put(
            $cacheKey,
            $this->snapshotSerializer->serialize($runtime),
            (int) config('heos.runtime_cache.ttl', 300),
        );

        return $runtime;
    }

    public function resolveSummary(TenantContext $context): WorkspaceRuntimeSummary
    {
        return $this->inner->resolveSummary($context);
    }

    private function isEnabled(): bool
    {
        return (bool) config('heos.runtime_cache.enabled', true);
    }
}
