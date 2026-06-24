<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class AuditLogPolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allowsPermission($user, 'audit.read');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $this->allowsPermission($user, 'audit.read')
            && $this->matchesTenantOrganization($auditLog);
    }

    private function allowsPermission(User $user, string $permissionKey): bool
    {
        if (! app()->bound(TenantContext::class)) {
            return false;
        }

        $context = app(TenantContext::class);

        if ($context->user->isNot($user)) {
            return false;
        }

        return $this->tenantAuthorizationService->allows($context, $permissionKey);
    }

    private function matchesTenantOrganization(AuditLog $auditLog): bool
    {
        if (! app()->bound(TenantContext::class)) {
            return false;
        }

        $context = app(TenantContext::class);

        return $auditLog->organization_id === $context->organization->id;
    }
}
