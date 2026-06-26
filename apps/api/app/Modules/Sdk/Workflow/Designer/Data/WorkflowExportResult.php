<?php

namespace App\Modules\Sdk\Workflow\Designer\Data;

readonly class WorkflowExportResult implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $workflowDefinitionPublicId,
        public string $workflowKey,
        public string $format,
        public array $payload,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'workflow_definition_public_id' => $this->workflowDefinitionPublicId,
            'workflow_key' => $this->workflowKey,
            'format' => $this->format,
            'payload' => $this->payload,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
