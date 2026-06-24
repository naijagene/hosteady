<?php

namespace App\Services\Invitation\Data;

readonly class InvitationAcceptedResult
{
    public function __construct(
        public string $membershipPublicId,
        public string $organizationPublicId,
        public string $invitationPublicId,
    ) {
    }
}
