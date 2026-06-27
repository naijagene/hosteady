<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkflowPackage;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class WorkflowMarketplacePolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'workflow.marketplace.read');
    }

    public function view(User $user): bool
    {
        return $this->allows($user, 'workflow.marketplace.read');
    }

    public function install(User $user): bool
    {
        return $this->allows($user, 'workflow.marketplace.install');
    }

    public function publish(User $user): bool
    {
        return $this->allows($user, 'workflow.marketplace.publish');
    }

    public function manage(User $user): bool
    {
        return $this->allows($user, 'workflow.marketplace.manage');
    }

    public function export(User $user): bool
    {
        return $this->allows($user, 'workflow.marketplace.export');
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
