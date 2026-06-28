<?php

namespace App\Modules\Sdk\Integration\Data;

readonly class IntegrationDeadLetterRecord implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $status,
        public string $reason,
        public ?string $eventPublicId,
        public ?string $dispatchPublicId,
        public array $payload,
        public ?string $errorMessage,
        public array $metadata,
        public ?string $createdAt,
        public ?string $resolvedAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['PublicId'] ?? ''),
            status: (string) ($data['status'] ?? $data['Status'] ?? ''),
            reason: (string) ($data['reason'] ?? $data['Reason'] ?? ''),
            eventPublicId: isset($data['event_public_id']) ? (string) $data['event_public_id'] : (isset($data['EventPublicId']) ? (string) $data['EventPublicId'] : null),
            dispatchPublicId: isset($data['dispatch_public_id']) ? (string) $data['dispatch_public_id'] : (isset($data['DispatchPublicId']) ? (string) $data['DispatchPublicId'] : null),
            payload: is_array($data['payload'] ?? $data['Payload'] ?? null) ? ($data['payload'] ?? $data['Payload']) : [],
            errorMessage: isset($data['error_message']) ? (string) $data['error_message'] : (isset($data['ErrorMessage']) ? (string) $data['ErrorMessage'] : null),
            metadata: is_array($data['metadata'] ?? $data['Metadata'] ?? null) ? ($data['metadata'] ?? $data['Metadata']) : [],
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : (isset($data['CreatedAt']) ? (string) $data['CreatedAt'] : null),
            resolvedAt: isset($data['resolved_at']) ? (string) $data['resolved_at'] : (isset($data['ResolvedAt']) ? (string) $data['ResolvedAt'] : null),
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'status' => $this->status,
            'reason' => $this->reason,
            'event_public_id' => $this->eventPublicId,
            'dispatch_public_id' => $this->dispatchPublicId,
            'payload' => $this->payload,
            'error_message' => $this->errorMessage,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt,
            'resolved_at' => $this->resolvedAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
