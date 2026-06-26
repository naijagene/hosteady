<?php

namespace App\Modules\Sdk\Workflow\Runtime\Data;

readonly class WorkflowExecutionReference implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>|null  $result
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     */
    public function __construct(
        public string $publicId,
        public string $nodeId,
        public string $nodeType,
        public string $status,
        public ?string $startedAt = null,
        public ?string $completedAt = null,
        public ?int $durationMs = null,
        public ?array $result = null,
        public array $warnings = [],
        public array $errors = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'node_id' => $this->nodeId,
            'node_type' => $this->nodeType,
            'status' => $this->status,
            'started_at' => $this->startedAt,
            'completed_at' => $this->completedAt,
            'duration_ms' => $this->durationMs,
            'result' => $this->result,
            'warnings' => $this->warnings,
            'errors' => $this->errors,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
