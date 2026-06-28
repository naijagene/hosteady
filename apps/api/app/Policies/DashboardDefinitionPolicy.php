<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class DashboardDefinitionPolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'dashboards.read');
    }

    public function view(User $user): bool
    {
        return $this->allows($user, 'dashboards.read');
    }

    public function manage(User $user): bool
    {
        return $this->allows($user, 'dashboards.manage');
    }

    public function render(User $user): bool
    {
        return $this->allows($user, 'dashboards.render');
    }

    public function export(User $user): bool
    {
        return $this->allows($user, 'dashboards.export');
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
