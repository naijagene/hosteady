<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class ReportDefinitionPolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'reports.read');
    }

    public function view(User $user): bool
    {
        return $this->allows($user, 'reports.read');
    }

    public function manage(User $user): bool
    {
        return $this->allows($user, 'reports.manage');
    }

    public function run(User $user): bool
    {
        return $this->allows($user, 'reports.run');
    }

    public function export(User $user): bool
    {
        return $this->allows($user, 'reports.export');
    }

    public function schedule(User $user): bool
    {
        return $this->allows($user, 'reports.schedule');
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
