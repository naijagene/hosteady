<?php

namespace App\Modules\Sdk\Workflow\Human\Data;

readonly class TaskComment implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $body,
        public ?string $authorMembershipPublicId = null,
        public ?string $authorUserPublicId = null,
        public ?string $createdAt = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'body' => $this->body,
            'author_membership_public_id' => $this->authorMembershipPublicId,
            'author_user_public_id' => $this->authorUserPublicId,
            'created_at' => $this->createdAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
