<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Navigation\NavigationPermissionBridge;
use App\Support\Tenant\TenantContext;

class NavigationDefinitionPolicy
{
    public function __construct(
        private readonly NavigationPermissionBridge $permissionBridge,
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

    public function delete(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canManage($context));
    }

    public function publish(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canPublish($context));
    }

    public function personalize(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canPersonalize($context));
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
