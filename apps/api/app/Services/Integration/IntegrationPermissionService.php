<?php

namespace App\Services\Integration;

use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class IntegrationPermissionService
{
    public function __construct(
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    public function canRead(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'integrations.read');
    }

    public function canManage(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'integrations.manage');
    }

    public function canPublish(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'integrations.publish');
    }

    public function canDispatch(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'integrations.dispatch');
    }

    public function canReplay(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'integrations.replay');
    }

    public function canAdmin(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'integrations.admin');
    }
}
