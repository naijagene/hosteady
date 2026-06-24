<?php

namespace App\Services\Organization\Data;

readonly class ProvisionedOrganizationResult
{
    public function __construct(
        public string $organizationPublicId,
        public string $workspacePublicId,
        public string $membershipPublicId,
        public string $organizationCode,
    ) {
    }
}
