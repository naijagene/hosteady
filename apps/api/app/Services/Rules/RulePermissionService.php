<?php

namespace App\Services\Rules;

use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class RulePermissionService
{
    public function __construct(
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    public function canRead(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'rules.read');
    }

    public function canManage(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'rules.manage');
    }

    public function canEvaluate(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'rules.evaluate');
    }

    public function canExecute(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'rules.execute');
    }

    public function canAdmin(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'rules.admin');
    }
}
