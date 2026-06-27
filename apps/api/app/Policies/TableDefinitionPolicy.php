<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class TableDefinitionPolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'tables.read');
    }

    public function view(User $user): bool
    {
        return $this->allows($user, 'tables.read');
    }

    public function manage(User $user): bool
    {
        return $this->allows($user, 'tables.manage');
    }

    public function query(User $user): bool
    {
        return $this->allows($user, 'tables.query');
    }

    public function export(User $user): bool
    {
        return $this->allows($user, 'tables.export');
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
