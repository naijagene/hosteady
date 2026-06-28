<?php

namespace App\Services\Notification;

use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class EnterpriseNotificationPermissionService
{
    public function __construct(
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    public function canRead(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'notifications.read');
    }

    public function canSend(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'notifications.send');
    }

    public function canManage(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'notifications.manage');
    }

    public function canTemplates(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'notifications.templates');
    }

    public function canPreferences(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'notifications.preferences');
    }

    public function canBroadcast(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'notifications.broadcast');
    }
}
