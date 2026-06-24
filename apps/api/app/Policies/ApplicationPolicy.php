<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class ApplicationPolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allowsPermission($user, 'applications.read');
    }

    public function view(User $user, Application $application): bool
    {
        return $this->allowsPermission($user, 'applications.read');
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
}
