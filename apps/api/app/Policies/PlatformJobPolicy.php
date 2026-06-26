<?php

namespace App\Policies;

use App\Models\PlatformJob;
use App\Models\User;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class PlatformJobPolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'jobs.read');
    }

    public function view(User $user, PlatformJob $job): bool
    {
        return $this->allows($user, 'jobs.read');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'jobs.dispatch');
    }

    public function cancel(User $user): bool
    {
        return $this->allows($user, 'jobs.manage') || $this->allows($user, 'jobs.dispatch');
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
