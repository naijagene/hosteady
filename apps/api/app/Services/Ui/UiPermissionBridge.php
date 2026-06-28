<?php

namespace App\Services\Ui;

use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class UiPermissionBridge
{
    public function __construct(
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    public function canRead(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'ui.read');
    }

    public function canManage(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'ui.manage');
    }

    public function canRender(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'ui.render');
    }

    public function canPersonalize(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'ui.personalize');
    }

    /**
     * @return array<string, bool>
     */
    public function renderPermissions(TenantContext $context): array
    {
        return [
            'read' => $this->canRead($context),
            'manage' => $this->canManage($context),
            'render' => $this->canRender($context),
            'personalize' => $this->canPersonalize($context),
        ];
    }
}
