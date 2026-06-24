<?php

namespace App\Services\Invitation\Data;

readonly class AcceptInvitationData
{
    public function __construct(
        public string $plainToken,
        public int $acceptingUserId,
    ) {
    }
}
