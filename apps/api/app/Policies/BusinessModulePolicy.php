<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class BusinessModulePolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'business.modules.read');
    }

    public function install(User $user): bool
    {
        return $this->allows($user, 'business.modules.install');
    }

    public function manage(User $user): bool
    {
        return $this->allows($user, 'business.modules.manage');
    }

    public function develop(User $user): bool
    {
        return $this->allows($user, 'business.modules.develop');
    }

    private function allows(User $user, string $permission): bool
    {
        if (! app()->bound(TenantContext::class)) {
            return false;
        }

        $context = app(TenantContext::class);

        if ($context->user->isNot($user)) {
            return false;
        }

        return $this->tenantAuthorizationService->allows($context, $permission);
    }
}
