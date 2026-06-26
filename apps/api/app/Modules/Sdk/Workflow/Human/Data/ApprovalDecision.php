<?php

namespace App\Modules\Sdk\Workflow\Human\Data;

readonly class ApprovalDecision implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $decisionType,
        public string $status,
        public ?string $comment = null,
        public ?string $decidedByMembershipPublicId = null,
        public ?string $decidedAt = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'decision_type' => $this->decisionType,
            'status' => $this->status,
            'comment' => $this->comment,
            'decided_by_membership_public_id' => $this->decidedByMembershipPublicId,
            'decided_at' => $this->decidedAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
