<?php

namespace App\Policies;

use App\Models\EnterpriseEntityRecord;
use App\Models\User;
use App\Services\DataRepository\EnterpriseEntityRecordPolicyResolverService;
use App\Support\Tenant\TenantContext;

class EnterpriseEntityRecordPolicy
{
    public function __construct(
        private readonly EnterpriseEntityRecordPolicyResolverService $policyResolver,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->policyResolver->canRead($context));
    }

    public function view(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->policyResolver->canRead($context));
    }

    public function create(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->policyResolver->canCreate($context));
    }

    public function update(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->policyResolver->canUpdate($context));
    }

    public function delete(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->policyResolver->canDelete($context));
    }

    public function restore(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->policyResolver->canRestore($context));
    }

    public function link(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->policyResolver->canLink($context));
    }

    public function query(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->policyResolver->canQuery($context));
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
