<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class WorkflowDefinitionPolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'workflow.read');
    }

    public function view(User $user): bool
    {
        return $this->allows($user, 'workflow.read');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'workflow.manage');
    }

    public function update(User $user): bool
    {
        return $this->allows($user, 'workflow.manage');
    }

    public function publish(User $user): bool
    {
        return $this->allows($user, 'workflow.publish');
    }

    public function archive(User $user): bool
    {
        return $this->allows($user, 'workflow.manage');
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
