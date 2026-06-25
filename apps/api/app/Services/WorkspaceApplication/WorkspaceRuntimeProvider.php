<?php

namespace App\Services\WorkspaceApplication;

use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeContext;
use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeSummary;
use App\Support\Tenant\TenantContext;

interface WorkspaceRuntimeProvider
{
    public function resolve(TenantContext $context, ?string $activeWorkspaceApplicationPublicId = null): WorkspaceRuntimeContext;

    public function resolveSummary(TenantContext $context): WorkspaceRuntimeSummary;
}
