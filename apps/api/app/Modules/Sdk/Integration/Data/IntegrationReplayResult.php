<?php

namespace App\Modules\Sdk\Integration\Data;

readonly class IntegrationReplayResult implements \JsonSerializable
{
    public function __construct(
        public string $eventPublicId,
        public string $replayEventPublicId,
        public string $status,
        public array $dispatches,
        public array $metadata,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            eventPublicId: (string) ($data['event_public_id'] ?? $data['EventPublicId'] ?? ''),
            replayEventPublicId: (string) ($data['replay_event_public_id'] ?? $data['ReplayEventPublicId'] ?? ''),
            status: (string) ($data['status'] ?? $data['Status'] ?? ''),
            dispatches: is_array($data['dispatches'] ?? $data['Dispatches'] ?? null) ? ($data['dispatches'] ?? $data['Dispatches']) : [],
            metadata: is_array($data['metadata'] ?? $data['Metadata'] ?? null) ? ($data['metadata'] ?? $data['Metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'event_public_id' => $this->eventPublicId,
            'replay_event_public_id' => $this->replayEventPublicId,
            'status' => $this->status,
            'dispatches' => $this->dispatches,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
