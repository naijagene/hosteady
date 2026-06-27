<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class FormDefinitionPolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'forms.read');
    }

    public function view(User $user): bool
    {
        return $this->allows($user, 'forms.read');
    }

    public function manage(User $user): bool
    {
        return $this->allows($user, 'forms.manage');
    }

    public function submit(User $user): bool
    {
        return $this->allows($user, 'forms.submit');
    }

    public function draft(User $user): bool
    {
        return $this->allows($user, 'forms.draft');
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
