<?php

namespace App\Modules\Sdk\Workflow\Automation\Data;

readonly class WorkflowTimerReference implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $publicId,
        public string $timerType,
        public string $status,
        public string $nodeId,
        public string $workflowInstancePublicId,
        public string $dueAt,
        public array $metadata = [],
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
            'timer_type' => $this->timerType,
            'status' => $this->status,
            'node_id' => $this->nodeId,
            'workflow_instance_public_id' => $this->workflowInstancePublicId,
            'due_at' => $this->dueAt,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
