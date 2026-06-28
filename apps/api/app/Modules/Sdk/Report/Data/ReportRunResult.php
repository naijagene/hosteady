<?php

namespace App\Modules\Sdk\Report\Data;

readonly class ReportRunResult implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $status,
        public array $parameters = [],
        public array $result = [],
        public ?string $startedAt = null,
        public ?string $completedAt = null,
        public ?int $durationMs = null,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? ''),
            status: (string) ($data['status'] ?? 'pending'),
            parameters: is_array($data['parameters'] ?? null) ? $data['parameters'] : [],
            result: is_array($data['result'] ?? null) ? $data['result'] : [],
            startedAt: isset($data['started_at']) ? (string) $data['started_at'] : null,
            completedAt: isset($data['completed_at']) ? (string) $data['completed_at'] : null,
            durationMs: isset($data['duration_ms']) ? (int) $data['duration_ms'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'status' => $this->status,
            'parameters' => $this->parameters,
            'result' => $this->result,
            'started_at' => $this->startedAt,
            'completed_at' => $this->completedAt,
            'duration_ms' => $this->durationMs,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
