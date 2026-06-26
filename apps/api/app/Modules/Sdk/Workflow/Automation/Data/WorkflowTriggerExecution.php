<?php

namespace App\Modules\Sdk\Workflow\Automation\Data;

readonly class WorkflowTriggerExecution implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $publicId,
        public string $rulePublicId,
        public string $triggerSource,
        public string $status,
        public ?string $workflowInstancePublicId = null,
        public ?string $eventName = null,
        public ?string $errorMessage = null,
        public ?string $executedAt = null,
        public array $metadata = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'rule_public_id' => $this->rulePublicId,
            'trigger_source' => $this->triggerSource,
            'status' => $this->status,
            'workflow_instance_public_id' => $this->workflowInstancePublicId,
            'event_name' => $this->eventName,
            'error_message' => $this->errorMessage,
            'executed_at' => $this->executedAt,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
