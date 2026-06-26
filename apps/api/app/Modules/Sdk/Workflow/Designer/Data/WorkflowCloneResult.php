<?php

namespace App\Modules\Sdk\Workflow\Designer\Data;

readonly class WorkflowCloneResult implements \JsonSerializable
{
    public function __construct(
        public string $sourceDefinitionPublicId,
        public string $clonedDefinitionPublicId,
        public string $workflowKey,
        public string $name,
        public ?string $snapshotPublicId = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source_definition_public_id' => $this->sourceDefinitionPublicId,
            'cloned_definition_public_id' => $this->clonedDefinitionPublicId,
            'workflow_key' => $this->workflowKey,
            'name' => $this->name,
            'snapshot_public_id' => $this->snapshotPublicId,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
