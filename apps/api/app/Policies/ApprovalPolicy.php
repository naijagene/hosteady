<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkflowHumanTask;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class ApprovalPolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'approval.read');
    }

    public function view(User $user): bool
    {
        return $this->allows($user, 'approval.read');
    }

    public function decide(User $user): bool
    {
        return $this->allows($user, 'approval.decide');
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
