<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Notification\EnterpriseNotificationPermissionService;
use App\Support\Tenant\TenantContext;

class EnterpriseNotificationPolicy
{
    public function __construct(
        private readonly EnterpriseNotificationPermissionService $permissionService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canRead($context));
    }

    public function view(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canRead($context));
    }

    public function create(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canSend($context));
    }

    public function update(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canRead($context));
    }

    public function delete(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canManage($context));
    }

    public function manage(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canManage($context));
    }

    public function templates(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canTemplates($context));
    }

    public function preferences(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canPreferences($context));
    }

    public function broadcast(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canBroadcast($context));
    }

    private function resolve(User $user, callable $callback): bool
    {
        if (! app()->bound(TenantContext::class)) {
            return false;
        }

        $context = app(TenantContext::class);

        if ($context->user->isNot($user)) {
            return false;
        }

        return $callback($context);
    }
}
