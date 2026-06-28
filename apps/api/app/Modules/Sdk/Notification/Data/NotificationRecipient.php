<?php

namespace App\Modules\Sdk\Notification\Data;

readonly class NotificationRecipient implements \JsonSerializable
{
    public function __construct(
        public string $membershipPublicId,
        public ?string $userPublicId,
        public ?string $displayName
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            membershipPublicId: (string) ($data['membership_public_id'] ?? $data['membershipPublicId'] ?? ''),
            userPublicId: isset($data['user_public_id']) ? (string) $data['user_public_id'] : (isset($data['userPublicId']) ? (string) $data['userPublicId'] : null),
            displayName: isset($data['display_name']) ? (string) $data['display_name'] : (isset($data['displayName']) ? (string) $data['displayName'] : null)
        );
    }

    public function toArray(): array
    {
        return [
            'membership_public_id' => $this->membershipPublicId,
            'user_public_id' => $this->userPublicId,
            'display_name' => $this->displayName
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
