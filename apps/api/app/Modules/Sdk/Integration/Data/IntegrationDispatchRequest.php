<?php

namespace App\Modules\Sdk\Integration\Data;

readonly class IntegrationDispatchRequest implements \JsonSerializable
{
    public function __construct(
        public string $eventPublicId,
        public ?string $endpointPublicId,
        public ?string $subscriptionKey,
        public array $metadata,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            eventPublicId: (string) ($data['event_public_id'] ?? $data['EventPublicId'] ?? ''),
            endpointPublicId: isset($data['endpoint_public_id']) ? (string) $data['endpoint_public_id'] : (isset($data['EndpointPublicId']) ? (string) $data['EndpointPublicId'] : null),
            subscriptionKey: isset($data['subscription_key']) ? (string) $data['subscription_key'] : (isset($data['SubscriptionKey']) ? (string) $data['SubscriptionKey'] : null),
            metadata: is_array($data['metadata'] ?? $data['Metadata'] ?? null) ? ($data['metadata'] ?? $data['Metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'event_public_id' => $this->eventPublicId,
            'endpoint_public_id' => $this->endpointPublicId,
            'subscription_key' => $this->subscriptionKey,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
