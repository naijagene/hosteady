<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Application\ApplicationPermissionBridge;
use App\Support\Tenant\TenantContext;

class ApplicationRuntimePolicy
{
    public function __construct(
        private readonly ApplicationPermissionBridge $permissionBridge,
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

    public function viewNavigation(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canReadNavigation($context));
    }

    public function viewWorkspaces(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canReadWorkspace($context));
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
