<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkflowCanvasSnapshot;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class WorkflowDesignerPolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'workflow.designer.read');
    }

    public function view(User $user): bool
    {
        return $this->allows($user, 'workflow.designer.read');
    }

    public function manage(User $user): bool
    {
        return $this->allows($user, 'workflow.designer.manage');
    }

    public function import(User $user): bool
    {
        return $this->allows($user, 'workflow.designer.import');
    }

    public function export(User $user): bool
    {
        return $this->allows($user, 'workflow.designer.export');
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
