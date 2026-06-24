<?php

namespace App\Support\Tenant;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Models\Workspace;

readonly class TenantContext
{
    public function __construct(
        public User $user,
        public Organization $organization,
        public OrganizationMembership $membership,
        public Workspace $workspace,
        public string $userPublicId,
        public string $organizationPublicId,
        public string $membershipPublicId,
        public string $workspacePublicId,
    ) {
    }

    public static function fromModels(
        User $user,
        Organization $organization,
        OrganizationMembership $membership,
        Workspace $workspace,
    ): self {
        return new self(
            user: $user,
            organization: $organization,
            membership: $membership,
            workspace: $workspace,
            userPublicId: $user->public_id,
            organizationPublicId: $organization->public_id,
            membershipPublicId: $membership->public_id,
            workspacePublicId: $workspace->public_id,
        );
    }
}
