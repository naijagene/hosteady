<?php

namespace App\Modules\Sdk\Workflow\Runtime\Data;

readonly class WorkflowInstanceReference implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>|null  $inputPayload
     * @param  array<string, mixed>|null  $result
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $publicId,
        public string $status,
        public string $definitionPublicId,
        public string $definitionName,
        public ?string $workflowKey = null,
        public ?string $versionPublicId = null,
        public ?string $currentNodeId = null,
        public ?array $inputPayload = null,
        public ?array $result = null,
        public array $warnings = [],
        public array $errors = [],
        public array $metadata = [],
        public ?string $startedAt = null,
        public ?string $completedAt = null,
        public ?int $durationMs = null,
        public ?string $createdAt = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'status' => $this->status,
            'definition_public_id' => $this->definitionPublicId,
            'definition_name' => $this->definitionName,
            'workflow_key' => $this->workflowKey,
            'version_public_id' => $this->versionPublicId,
            'current_node_id' => $this->currentNodeId,
            'input_payload' => $this->inputPayload,
            'result' => $this->result,
            'warnings' => $this->warnings,
            'errors' => $this->errors,
            'metadata' => $this->metadata,
            'started_at' => $this->startedAt,
            'completed_at' => $this->completedAt,
            'duration_ms' => $this->durationMs,
            'created_at' => $this->createdAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
