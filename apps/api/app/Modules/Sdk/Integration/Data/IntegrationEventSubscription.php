<?php

namespace App\Modules\Sdk\Integration\Data;

readonly class IntegrationEventSubscription implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $subscriptionKey,
        public string $eventPattern,
        public ?string $endpointKey,
        public string $status,
        public ?string $moduleKey,
        public array $filters,
        public array $transform,
        public array $retryPolicy,
        public array $metadata,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['PublicId'] ?? ''),
            subscriptionKey: (string) ($data['subscription_key'] ?? $data['SubscriptionKey'] ?? ''),
            eventPattern: (string) ($data['event_pattern'] ?? $data['EventPattern'] ?? ''),
            endpointKey: isset($data['endpoint_key']) ? (string) $data['endpoint_key'] : (isset($data['EndpointKey']) ? (string) $data['EndpointKey'] : null),
            status: (string) ($data['status'] ?? $data['Status'] ?? ''),
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : (isset($data['ModuleKey']) ? (string) $data['ModuleKey'] : null),
            filters: is_array($data['filters'] ?? $data['Filters'] ?? null) ? ($data['filters'] ?? $data['Filters']) : [],
            transform: is_array($data['transform'] ?? $data['Transform'] ?? null) ? ($data['transform'] ?? $data['Transform']) : [],
            retryPolicy: is_array($data['retry_policy'] ?? $data['RetryPolicy'] ?? null) ? ($data['retry_policy'] ?? $data['RetryPolicy']) : [],
            metadata: is_array($data['metadata'] ?? $data['Metadata'] ?? null) ? ($data['metadata'] ?? $data['Metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'subscription_key' => $this->subscriptionKey,
            'event_pattern' => $this->eventPattern,
            'endpoint_key' => $this->endpointKey,
            'status' => $this->status,
            'module_key' => $this->moduleKey,
            'filters' => $this->filters,
            'transform' => $this->transform,
            'retry_policy' => $this->retryPolicy,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
