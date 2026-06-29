<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Personalization\PersonalizationPermissionBridge;
use App\Support\Tenant\TenantContext;

class PersonalizationProfilePolicy
{
    public function __construct(
        private readonly PersonalizationPermissionBridge $permissionBridge,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canRead($context));
    }

    public function view(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canRead($context));
    }

    public function create(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canWrite($context));
    }

    public function update(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canWrite($context));
    }

    private function resolve(User $user, callable $callback): bool
    {
        if (! app()->bound(TenantContext::class)) {
            return false;
        }

        $context = app(TenantContext::class);
        if ($context->user->id !== $user->id) {
            return false;
        }

        return (bool) $callback($context);
    }
}
