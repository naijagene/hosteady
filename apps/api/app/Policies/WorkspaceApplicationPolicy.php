<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkspaceApplication;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class WorkspaceApplicationPolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allowsPermission($user, 'workspace.applications.read');
    }

    public function view(User $user, WorkspaceApplication $workspaceApplication): bool
    {
        return $this->allowsPermission($user, 'workspace.applications.read')
            && $this->matchesTenantWorkspace($workspaceApplication);
    }

    public function enable(User $user, ?WorkspaceApplication $workspaceApplication = null): bool
    {
        if (! $this->allowsPermission($user, 'workspace.applications.enable')) {
            return false;
        }

        if ($workspaceApplication === null) {
            return true;
        }

        return $this->matchesTenantWorkspace($workspaceApplication);
    }

    public function manage(User $user, WorkspaceApplication $workspaceApplication): bool
    {
        return $this->allowsPermission($user, 'workspace.applications.manage')
            && $this->matchesTenantWorkspace($workspaceApplication);
    }

    public function configure(User $user, WorkspaceApplication $workspaceApplication): bool
    {
        return $this->allowsPermission($user, 'workspace.applications.configure')
            && $this->matchesTenantWorkspace($workspaceApplication);
    }

    private function allowsPermission(User $user, string $permissionKey): bool
    {
        if (! app()->bound(TenantContext::class)) {
            return false;
        }

        $context = app(TenantContext::class);

        if ($context->user->isNot($user)) {
            return false;
        }

        return $this->tenantAuthorizationService->allows($context, $permissionKey);
    }

    private function matchesTenantWorkspace(WorkspaceApplication $workspaceApplication): bool
    {
        if (! app()->bound(TenantContext::class)) {
            return false;
        }

        $context = app(TenantContext::class);

        return $workspaceApplication->organization_id === $context->organization->id
            && $workspaceApplication->workspace_id === $context->workspace->id;
    }
}
