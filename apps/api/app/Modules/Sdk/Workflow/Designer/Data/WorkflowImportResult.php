<?php

namespace App\Modules\Sdk\Workflow\Designer\Data;

readonly class WorkflowImportResult implements \JsonSerializable
{
    public function __construct(
        public string $workflowDefinitionPublicId,
        public string $workflowKey,
        public string $name,
        public string $format,
        public string $status,
        public ?string $snapshotPublicId = null,
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
            'name' => $this->name,
            'format' => $this->format,
            'status' => $this->status,
            'snapshot_public_id' => $this->snapshotPublicId,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
