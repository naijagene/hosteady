<?php

namespace App\Policies;

use App\Models\ScheduledTask;
use App\Models\User;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class ScheduledTaskPolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'scheduler.read') || $this->allows($user, 'jobs.read');
    }

    public function view(User $user, ScheduledTask $task): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'scheduler.manage');
    }

    public function update(User $user): bool
    {
        return $this->allows($user, 'scheduler.manage');
    }

    public function delete(User $user): bool
    {
        return $this->allows($user, 'scheduler.manage');
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
