<?php

namespace App\Modules\Sdk\Integration\Data;

readonly class IntegrationReplayRequest implements \JsonSerializable
{
    public function __construct(
        public string $eventPublicId,
        public array $metadata,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            eventPublicId: (string) ($data['event_public_id'] ?? $data['EventPublicId'] ?? ''),
            metadata: is_array($data['metadata'] ?? $data['Metadata'] ?? null) ? ($data['metadata'] ?? $data['Metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'event_public_id' => $this->eventPublicId,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
