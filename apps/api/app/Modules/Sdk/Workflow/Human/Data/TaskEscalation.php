<?php

namespace App\Modules\Sdk\Workflow\Human\Data;

readonly class TaskEscalation implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $publicId,
        public string $escalationRule,
        public ?string $escalatedMembershipPublicId = null,
        public ?string $escalatedUserPublicId = null,
        public ?string $escalatedAt = null,
        public ?string $reason = null,
        public array $metadata = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'escalation_rule' => $this->escalationRule,
            'escalated_membership_public_id' => $this->escalatedMembershipPublicId,
            'escalated_user_public_id' => $this->escalatedUserPublicId,
            'escalated_at' => $this->escalatedAt,
            'reason' => $this->reason,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
