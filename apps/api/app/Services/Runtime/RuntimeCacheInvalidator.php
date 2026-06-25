<?php

namespace App\Services\Runtime;

use App\Models\WorkspaceApplication;
use App\Support\Tenant\TenantContext;

class RuntimeCacheInvalidator
{
    public function __construct(
        private readonly RuntimeCacheStore $cacheStore,
    ) {
    }

    public function invalidateTenantContext(TenantContext $context): void
    {
        $this->invalidateWorkspace($context->organizationPublicId, $context->workspacePublicId);
    }

    public function invalidateWorkspace(string $organizationPublicId, string $workspacePublicId): void
    {
        $this->cacheStore->incrementGeneration($organizationPublicId, $workspacePublicId);
    }

    public function invalidateForApplicationCatalogChange(string $applicationId): void
    {
        WorkspaceApplication::query()
            ->where('application_id', $applicationId)
            ->whereNull('deleted_at')
            ->with(['organization', 'workspace'])
            ->get()
            ->each(function (WorkspaceApplication $workspaceApplication) {
                $this->invalidateWorkspace(
                    $workspaceApplication->organization->public_id,
                    $workspaceApplication->workspace->public_id,
                );
            });
    }
}
