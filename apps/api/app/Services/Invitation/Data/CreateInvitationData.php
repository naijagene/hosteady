<?php

namespace App\Services\Invitation\Data;

readonly class CreateInvitationData
{
    /**
     * @param  list<string>  $rolePublicIds
     */
    public function __construct(
        public string $organizationPublicId,
        public int $invitedByUserId,
        public string $email,
        public array $rolePublicIds,
        public ?string $message = null,
        public int $expiresInDays = 7,
    ) {
    }
}
