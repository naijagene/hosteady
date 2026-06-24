<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class OrganizationPolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function view(User $user, Organization $organization): bool
    {
        return $this->authorizePermission($user, $organization, 'organization.read');
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->authorizePermission($user, $organization, 'organization.update');
    }

    private function authorizePermission(User $user, Organization $organization, string $permissionKey): bool
    {
        if (! app()->bound(TenantContext::class)) {
            return false;
        }

        $context = app(TenantContext::class);

        if ($context->user->isNot($user) || $context->organization->isNot($organization)) {
            return false;
        }

        return $this->tenantAuthorizationService->allows($context, $permissionKey);
    }
}
