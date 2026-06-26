<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkflowInstance;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class WorkflowInstancePolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'workflow.runtime.read');
    }

    public function view(User $user): bool
    {
        return $this->allows($user, 'workflow.runtime.read');
    }

    public function execute(User $user): bool
    {
        return $this->allows($user, 'workflow.execute');
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
