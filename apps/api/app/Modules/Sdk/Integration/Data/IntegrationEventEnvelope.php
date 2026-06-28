<?php

namespace App\Modules\Sdk\Integration\Data;

readonly class IntegrationEventEnvelope implements \JsonSerializable
{
    public function __construct(
        public string $eventName,
        public ?string $eventVersion,
        public string $direction,
        public string $sourceType,
        public ?string $sourceModuleKey,
        public ?string $sourceEntityKey,
        public ?string $sourcePublicId,
        public ?string $correlationId,
        public ?string $idempotencyKey,
        public array $payload,
        public array $headers,
        public array $metadata,
        public bool $forceRepublish,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            eventName: (string) ($data['event_name'] ?? $data['eventName'] ?? $data['EventName'] ?? ''),
            eventVersion: isset($data['event_version']) ? (string) $data['event_version'] : (isset($data['eventVersion']) ? (string) $data['eventVersion'] : (isset($data['EventVersion']) ? (string) $data['EventVersion'] : null)),
            direction: (string) ($data['direction'] ?? $data['Direction'] ?? ''),
            sourceType: (string) ($data['source_type'] ?? $data['sourceType'] ?? $data['SourceType'] ?? ''),
            sourceModuleKey: isset($data['source_module_key']) ? (string) $data['source_module_key'] : (isset($data['sourceModuleKey']) ? (string) $data['sourceModuleKey'] : (isset($data['SourceModuleKey']) ? (string) $data['SourceModuleKey'] : null)),
            sourceEntityKey: isset($data['source_entity_key']) ? (string) $data['source_entity_key'] : (isset($data['sourceEntityKey']) ? (string) $data['sourceEntityKey'] : (isset($data['SourceEntityKey']) ? (string) $data['SourceEntityKey'] : null)),
            sourcePublicId: isset($data['source_public_id']) ? (string) $data['source_public_id'] : (isset($data['sourcePublicId']) ? (string) $data['sourcePublicId'] : (isset($data['SourcePublicId']) ? (string) $data['SourcePublicId'] : null)),
            correlationId: isset($data['correlation_id']) ? (string) $data['correlation_id'] : (isset($data['correlationId']) ? (string) $data['correlationId'] : (isset($data['CorrelationId']) ? (string) $data['CorrelationId'] : null)),
            idempotencyKey: isset($data['idempotency_key']) ? (string) $data['idempotency_key'] : (isset($data['idempotencyKey']) ? (string) $data['idempotencyKey'] : (isset($data['IdempotencyKey']) ? (string) $data['IdempotencyKey'] : null)),
            payload: is_array($data['payload'] ?? $data['Payload'] ?? null) ? ($data['payload'] ?? $data['Payload']) : [],
            headers: is_array($data['headers'] ?? $data['Headers'] ?? null) ? ($data['headers'] ?? $data['Headers']) : [],
            metadata: is_array($data['metadata'] ?? $data['Metadata'] ?? null) ? ($data['metadata'] ?? $data['Metadata']) : [],
            forceRepublish: (bool) ($data['force_republish'] ?? $data['forceRepublish'] ?? $data['ForceRepublish'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName,
            'event_version' => $this->eventVersion,
            'direction' => $this->direction,
            'source_type' => $this->sourceType,
            'source_module_key' => $this->sourceModuleKey,
            'source_entity_key' => $this->sourceEntityKey,
            'source_public_id' => $this->sourcePublicId,
            'correlation_id' => $this->correlationId,
            'idempotency_key' => $this->idempotencyKey,
            'payload' => $this->payload,
            'headers' => $this->headers,
            'metadata' => $this->metadata,
            'force_republish' => $this->forceRepublish,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
