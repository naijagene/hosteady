<?php

namespace App\Policies;

use App\Models\PlatformFile;
use App\Models\User;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class PlatformFilePolicy
{
    public function __construct(
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'files.read');
    }

    public function view(User $user, PlatformFile $file): bool
    {
        return $this->allows($user, 'files.read');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'files.upload');
    }

    public function update(User $user): bool
    {
        return $this->allows($user, 'files.manage')
            || $this->allows($user, 'files.upload');
    }

    public function delete(User $user): bool
    {
        return $this->allows($user, 'files.manage')
            || $this->allows($user, 'files.upload');
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
