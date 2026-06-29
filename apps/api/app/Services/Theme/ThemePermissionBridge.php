<?php

namespace App\Services\Theme;

use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class ThemePermissionBridge
{
    public function __construct(
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    public function canRead(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'themes.read');
    }

    public function canManage(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'themes.manage');
    }

    public function canPublish(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'themes.publish');
    }

    public function canManageBrand(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'themes.brand');
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
            'brand' => $this->canManageBrand($context),
        ];
    }
}
