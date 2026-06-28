<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Rules\RulePermissionService;
use App\Support\Tenant\TenantContext;

class EnterpriseBusinessRulePolicy
{
    public function __construct(
        private readonly RulePermissionService $permissionService,
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

    public function evaluate(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canEvaluate($context));
    }

    public function execute(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionService->canExecute($context));
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
