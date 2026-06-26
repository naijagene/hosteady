<?php

namespace App\Modules\Sdk\Workflow\Automation\Data;

readonly class WorkflowAutomationRule implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $triggerConfig
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $publicId,
        public string $triggerType,
        public string $status,
        public string $workflowDefinitionPublicId,
        public ?string $workflowDefinitionName = null,
        public array $triggerConfig = [],
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
            'trigger_type' => $this->triggerType,
            'status' => $this->status,
            'workflow_definition_public_id' => $this->workflowDefinitionPublicId,
            'workflow_definition_name' => $this->workflowDefinitionName,
            'trigger_config' => $this->triggerConfig,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
