<?php

namespace App\Services\Runtime;

use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeSummary;
use App\Support\Tenant\TenantContext;

class RuntimeCacheKeyBuilder
{
    public function __construct(
        private readonly RuntimeCacheStore $cacheStore,
    ) {
    }

    public function build(
        TenantContext $context,
        WorkspaceRuntimeSummary $summary,
        ?string $activeWorkspaceApplicationPublicId = null,
    ): string {
        $generation = $this->cacheStore->currentGeneration(
            $context->organizationPublicId,
            $context->workspacePublicId,
        );

        return sprintf(
            'heos:runtime:v1:schema%d:%s:%s:%d:%s:%d:%s',
            (int) config('heos.runtime_cache.schema_version', 1),
            $context->organizationPublicId,
            $context->workspacePublicId,
            $generation,
            $summary->runtimeVersion,
            $summary->settingsVersion,
            $activeWorkspaceApplicationPublicId ?? 'none',
        );
    }
}
