<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Document\EnterpriseDocumentPermissionService;
use App\Support\Tenant\TenantContext;

class EnterpriseDocumentPolicy
{
    public function __construct(
        private readonly EnterpriseDocumentPermissionService $permissionService,
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
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canUpload($context));
    }

    public function update(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canUpdate($context));
    }

    public function delete(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canDelete($context));
    }

    public function version(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canVersion($context));
    }

    public function attach(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canAttach($context));
    }

    public function manage(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canManage($context));
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
