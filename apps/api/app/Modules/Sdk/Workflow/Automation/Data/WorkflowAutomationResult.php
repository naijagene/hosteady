<?php

namespace App\Modules\Sdk\Workflow\Automation\Data;

readonly class WorkflowAutomationResult implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $status,
        public ?string $workflowInstancePublicId = null,
        public ?string $errorMessage = null,
        public array $metadata = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'workflow_instance_public_id' => $this->workflowInstancePublicId,
            'error_message' => $this->errorMessage,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
