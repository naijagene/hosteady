<?php

namespace App\Policies;

use App\Models\PlatformNotification;
use App\Models\User;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class PlatformNotificationPolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'notifications.read');
    }

    public function update(User $user, PlatformNotification $notification): bool
    {
        if (! $this->allows($user, 'notifications.read')) {
            return false;
        }

        $context = app(TenantContext::class);

        return $notification->organization_id === $context->organization->id
            && $notification->recipient_membership_id === $context->membership->id;
    }

    private function allows(User $user, string $permission): bool
    {
        if (! app()->bound(TenantContext::class)) {
            return false;
        }

        $context = app(TenantContext::class);

        if ($context->user->isNot($user)) {
            return false;
        }

        return $this->tenantAuthorizationService->allows($context, $permission);
    }
}
