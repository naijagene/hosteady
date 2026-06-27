<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class EntityDefinitionPolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'entities.read');
    }

    public function view(User $user): bool
    {
        return $this->allows($user, 'entities.read');
    }

    public function manage(User $user): bool
    {
        return $this->allows($user, 'entities.manage');
    }

    public function comment(User $user): bool
    {
        return $this->allows($user, 'entities.comment');
    }

    public function tag(User $user): bool
    {
        return $this->allows($user, 'entities.tag');
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
