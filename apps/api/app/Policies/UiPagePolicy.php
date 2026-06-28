<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Ui\UiPermissionBridge;
use App\Support\Tenant\TenantContext;

class UiPagePolicy
{
    public function __construct(
        private readonly UiPermissionBridge $permissionBridge,
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

    public function render(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canRender($context));
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
