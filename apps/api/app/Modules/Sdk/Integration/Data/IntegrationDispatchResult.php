<?php

namespace App\Modules\Sdk\Integration\Data;

readonly class IntegrationDispatchResult implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $status,
        public int $attempt,
        public int $maxAttempts,
        public array $request,
        public array $response,
        public ?string $errorMessage,
        public ?string $correlationId,
        public ?string $dispatchedAt,
        public ?string $completedAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['PublicId'] ?? ''),
            status: (string) ($data['status'] ?? $data['Status'] ?? ''),
            attempt: (int) ($data['attempt'] ?? $data['Attempt'] ?? 0),
            maxAttempts: (int) ($data['max_attempts'] ?? $data['MaxAttempts'] ?? 0),
            request: is_array($data['request'] ?? $data['Request'] ?? null) ? ($data['request'] ?? $data['Request']) : [],
            response: is_array($data['response'] ?? $data['Response'] ?? null) ? ($data['response'] ?? $data['Response']) : [],
            errorMessage: isset($data['error_message']) ? (string) $data['error_message'] : (isset($data['ErrorMessage']) ? (string) $data['ErrorMessage'] : null),
            correlationId: isset($data['correlation_id']) ? (string) $data['correlation_id'] : (isset($data['CorrelationId']) ? (string) $data['CorrelationId'] : null),
            dispatchedAt: isset($data['dispatched_at']) ? (string) $data['dispatched_at'] : (isset($data['DispatchedAt']) ? (string) $data['DispatchedAt'] : null),
            completedAt: isset($data['completed_at']) ? (string) $data['completed_at'] : (isset($data['CompletedAt']) ? (string) $data['CompletedAt'] : null),
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'status' => $this->status,
            'attempt' => $this->attempt,
            'max_attempts' => $this->maxAttempts,
            'request' => $this->request,
            'response' => $this->response,
            'error_message' => $this->errorMessage,
            'correlation_id' => $this->correlationId,
            'dispatched_at' => $this->dispatchedAt,
            'completed_at' => $this->completedAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
