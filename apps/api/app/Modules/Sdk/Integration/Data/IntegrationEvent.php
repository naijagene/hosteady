<?php

namespace App\Modules\Sdk\Integration\Data;

readonly class IntegrationEvent implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $eventName,
        public ?string $eventVersion,
        public string $direction,
        public string $sourceType,
        public ?string $sourceModuleKey,
        public ?string $sourceEntityKey,
        public ?string $sourcePublicId,
        public ?string $correlationId,
        public ?string $idempotencyKey,
        public string $status,
        public array $payload,
        public array $headers,
        public array $metadata,
        public ?string $occurredAt,
        public ?string $publishedAt,
        public ?string $createdAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['PublicId'] ?? ''),
            eventName: (string) ($data['event_name'] ?? $data['EventName'] ?? ''),
            eventVersion: isset($data['event_version']) ? (string) $data['event_version'] : (isset($data['EventVersion']) ? (string) $data['EventVersion'] : null),
            direction: (string) ($data['direction'] ?? $data['Direction'] ?? ''),
            sourceType: (string) ($data['source_type'] ?? $data['SourceType'] ?? ''),
            sourceModuleKey: isset($data['source_module_key']) ? (string) $data['source_module_key'] : (isset($data['SourceModuleKey']) ? (string) $data['SourceModuleKey'] : null),
            sourceEntityKey: isset($data['source_entity_key']) ? (string) $data['source_entity_key'] : (isset($data['SourceEntityKey']) ? (string) $data['SourceEntityKey'] : null),
            sourcePublicId: isset($data['source_public_id']) ? (string) $data['source_public_id'] : (isset($data['SourcePublicId']) ? (string) $data['SourcePublicId'] : null),
            correlationId: isset($data['correlation_id']) ? (string) $data['correlation_id'] : (isset($data['CorrelationId']) ? (string) $data['CorrelationId'] : null),
            idempotencyKey: isset($data['idempotency_key']) ? (string) $data['idempotency_key'] : (isset($data['IdempotencyKey']) ? (string) $data['IdempotencyKey'] : null),
            status: (string) ($data['status'] ?? $data['Status'] ?? ''),
            payload: is_array($data['payload'] ?? $data['Payload'] ?? null) ? ($data['payload'] ?? $data['Payload']) : [],
            headers: is_array($data['headers'] ?? $data['Headers'] ?? null) ? ($data['headers'] ?? $data['Headers']) : [],
            metadata: is_array($data['metadata'] ?? $data['Metadata'] ?? null) ? ($data['metadata'] ?? $data['Metadata']) : [],
            occurredAt: isset($data['occurred_at']) ? (string) $data['occurred_at'] : (isset($data['OccurredAt']) ? (string) $data['OccurredAt'] : null),
            publishedAt: isset($data['published_at']) ? (string) $data['published_at'] : (isset($data['PublishedAt']) ? (string) $data['PublishedAt'] : null),
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : (isset($data['CreatedAt']) ? (string) $data['CreatedAt'] : null),
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'event_name' => $this->eventName,
            'event_version' => $this->eventVersion,
            'direction' => $this->direction,
            'source_type' => $this->sourceType,
            'source_module_key' => $this->sourceModuleKey,
            'source_entity_key' => $this->sourceEntityKey,
            'source_public_id' => $this->sourcePublicId,
            'correlation_id' => $this->correlationId,
            'idempotency_key' => $this->idempotencyKey,
            'status' => $this->status,
            'payload' => $this->payload,
            'headers' => $this->headers,
            'metadata' => $this->metadata,
            'occurred_at' => $this->occurredAt,
            'published_at' => $this->publishedAt,
            'created_at' => $this->createdAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
