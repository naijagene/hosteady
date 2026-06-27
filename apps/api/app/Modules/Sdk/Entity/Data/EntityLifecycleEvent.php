<?php

namespace App\Modules\Sdk\Entity\Data;

readonly class EntityLifecycleEvent implements \JsonSerializable
{
    public function __construct(
        public string $eventType,
        public string $moduleKey,
        public string $entityKey,
        public ?string $entityPublicId = null,
        public array $payload = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            eventType: (string) ($data['event_type'] ?? ''),
            moduleKey: (string) ($data['module_key'] ?? ''),
            entityKey: (string) ($data['entity_key'] ?? ''),
            entityPublicId: isset($data['entity_public_id']) ? (string) $data['entity_public_id'] : null,
            payload: is_array($data['payload'] ?? null) ? $data['payload'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'event_type' => $this->eventType,
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'entity_public_id' => $this->entityPublicId,
            'payload' => $this->payload,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
