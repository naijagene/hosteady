<?php

namespace App\Services\Invitation\Data;

readonly class InvitationCreatedResult
{
    public function __construct(
        public string $invitationPublicId,
        public string $invitationCode,
        public string $plainToken,
    ) {
    }
}
