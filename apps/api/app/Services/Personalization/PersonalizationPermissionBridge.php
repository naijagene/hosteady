<?php

namespace App\Services\Personalization;

use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class PersonalizationPermissionBridge
{
    public function __construct(
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    public function canRead(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'personalization.read');
    }

    public function canWrite(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'personalization.write')
            || $this->authorizationService->allows($context, 'personalization.manage');
    }
}
