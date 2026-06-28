<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Integration\IntegrationPermissionService;
use App\Support\Tenant\TenantContext;

class EnterpriseIntegrationPolicy
{
    public function __construct(
        private readonly IntegrationPermissionService $permissionService,
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
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canManage($context));
    }

    public function update(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canManage($context));
    }

    public function publish(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canPublish($context));
    }

    public function dispatch(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canDispatch($context));
    }

    public function replay(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canReplay($context));
    }

    public function admin(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canAdmin($context));
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
