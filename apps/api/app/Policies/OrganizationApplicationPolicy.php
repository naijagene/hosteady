<?php

namespace App\Policies;

use App\Models\OrganizationApplication;
use App\Models\User;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class OrganizationApplicationPolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allowsPermission($user, 'applications.read');
    }

    public function view(User $user, OrganizationApplication $organizationApplication): bool
    {
        return $this->allowsPermission($user, 'applications.read')
            && $this->matchesTenantOrganization($organizationApplication);
    }

    public function install(User $user): bool
    {
        return $this->allowsPermission($user, 'applications.install');
    }

    public function configure(User $user, OrganizationApplication $organizationApplication): bool
    {
        return $this->allowsPermission($user, 'applications.configure')
            && $this->matchesTenantOrganization($organizationApplication);
    }

    public function uninstall(User $user, OrganizationApplication $organizationApplication): bool
    {
        return $this->allowsPermission($user, 'applications.uninstall')
            && $this->matchesTenantOrganization($organizationApplication);
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

    private function matchesTenantOrganization(OrganizationApplication $organizationApplication): bool
    {
        if (! app()->bound(TenantContext::class)) {
            return false;
        }

        $context = app(TenantContext::class);

        return $organizationApplication->organization_id === $context->organization->id;
    }
}
