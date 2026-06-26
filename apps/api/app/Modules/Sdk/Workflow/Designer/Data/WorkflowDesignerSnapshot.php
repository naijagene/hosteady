<?php

namespace App\Modules\Sdk\Workflow\Designer\Data;

readonly class WorkflowDesignerSnapshot implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $workflowDefinitionPublicId,
        public string $status,
        public WorkflowCanvas $canvas,
        public ?string $workflowVersionPublicId = null,
        public ?string $createdAt = null,
        public ?string $createdByUserPublicId = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'workflow_definition_public_id' => $this->workflowDefinitionPublicId,
            'workflow_version_public_id' => $this->workflowVersionPublicId,
            'status' => $this->status,
            'canvas' => $this->canvas->toArray(),
            'created_at' => $this->createdAt,
            'created_by_user_public_id' => $this->createdByUserPublicId,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
