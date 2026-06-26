<?php

namespace App\Modules\Sdk\Workflow\Human\Data;

readonly class TaskHistory implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $eventType,
        public string $occurredAt,
        public ?string $actorMembershipPublicId = null,
        public ?string $summary = null,
        public array $metadata = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event_type' => $this->eventType,
            'occurred_at' => $this->occurredAt,
            'actor_membership_public_id' => $this->actorMembershipPublicId,
            'summary' => $this->summary,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
