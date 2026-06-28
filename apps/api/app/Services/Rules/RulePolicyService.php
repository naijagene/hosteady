<?php

namespace App\Services\Rules;

use App\Modules\Sdk\Rules\Contracts\RulePolicyProvider;
use App\Support\Tenant\TenantContext;

class RulePolicyService implements RulePolicyProvider
{
    public function __construct(
        private readonly RulePermissionService $permissionService,
    ) {
    }

    public function canEvaluate(TenantContext $context): bool
    {
        return $this->permissionService->canEvaluate($context);
    }

    public function canExecute(TenantContext $context): bool
    {
        return $this->permissionService->canExecute($context);
    }

    public function canManage(TenantContext $context): bool
    {
        return $this->permissionService->canManage($context);
    }
}
