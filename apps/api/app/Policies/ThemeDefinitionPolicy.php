<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Theme\ThemePermissionBridge;
use App\Support\Tenant\TenantContext;

class ThemeDefinitionPolicy
{
    public function __construct(
        private readonly ThemePermissionBridge $permissionBridge,
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
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canManage($context));
    }

    public function update(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canManage($context));
    }

    public function publish(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canPublish($context));
    }

    public function brand(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canManageBrand($context));
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
