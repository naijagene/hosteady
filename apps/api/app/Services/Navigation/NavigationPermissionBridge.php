<?php

namespace App\Services\Navigation;

use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class NavigationPermissionBridge
{
    public function __construct(
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    public function canRead(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'navigation.read');
    }

    public function canManage(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'navigation.manage');
    }

    public function canPublish(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'navigation.publish');
    }

    public function canPersonalize(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'navigation.personalize');
    }

    /**
     * @return array<string, bool>
     */
    public function renderPermissions(TenantContext $context): array
    {
        return [
            'read' => $this->canRead($context),
            'manage' => $this->canManage($context),
            'publish' => $this->canPublish($context),
            'personalize' => $this->canPersonalize($context),
        ];
    }
}
