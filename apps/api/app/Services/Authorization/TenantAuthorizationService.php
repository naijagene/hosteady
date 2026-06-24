<?php

namespace App\Services\Authorization;

use App\Enums\RoleStatus;
use App\Models\Permission;
use App\Models\Role;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Collection;

class TenantAuthorizationService
{
    /**
     * @var array<string, list<string>>
     */
    private array $permissionCache = [];

    public function allows(TenantContext $context, string $permissionKey): bool
    {
        return in_array($permissionKey, $this->permissionsFor($context), true);
    }

    /**
     * @return list<string>
     */
    public function permissionsFor(TenantContext $context): array
    {
        $cacheKey = $context->membershipPublicId;

        if (isset($this->permissionCache[$cacheKey])) {
            return $this->permissionCache[$cacheKey];
        }

        $roleIds = $context->membership->memberRoles()
            ->pluck('role_id');

        if ($roleIds->isEmpty()) {
            return $this->permissionCache[$cacheKey] = [];
        }

        $roles = Role::query()
            ->whereIn('id', $roleIds)
            ->where('organization_id', $context->organization->id)
            ->where('status', RoleStatus::Active)
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($roles->isEmpty()) {
            return $this->permissionCache[$cacheKey] = [];
        }

        /** @var Collection<int, Permission> $permissions */
        $permissions = Permission::query()
            ->whereHas('roles', function ($query) use ($roles) {
                $query->whereIn('roles.id', $roles);
            })
            ->orderBy('key')
            ->get();

        return $this->permissionCache[$cacheKey] = $permissions
            ->pluck('key')
            ->values()
            ->all();
    }
}
