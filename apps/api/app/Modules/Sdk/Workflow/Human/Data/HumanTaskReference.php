<?php

namespace App\Modules\Sdk\Workflow\Human\Data;

readonly class HumanTaskReference implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $publicId,
        public string $status,
        public string $taskType,
        public string $title,
        public ?string $description = null,
        public ?string $priority = null,
        public ?string $nodeId = null,
        public ?string $workflowInstancePublicId = null,
        public ?string $workflowDefinitionName = null,
        public ?string $assigneeMembershipPublicId = null,
        public ?string $assigneeUserPublicId = null,
        public ?string $assigneeRoleKey = null,
        public ?string $assignedAt = null,
        public ?string $openedAt = null,
        public ?string $completedAt = null,
        public ?string $dueAt = null,
        public ?string $approvalStatus = null,
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
            'status' => $this->status,
            'task_type' => $this->taskType,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'node_id' => $this->nodeId,
            'workflow_instance_public_id' => $this->workflowInstancePublicId,
            'workflow_definition_name' => $this->workflowDefinitionName,
            'assignee_membership_public_id' => $this->assigneeMembershipPublicId,
            'assignee_user_public_id' => $this->assigneeUserPublicId,
            'assignee_role_key' => $this->assigneeRoleKey,
            'assigned_at' => $this->assignedAt,
            'opened_at' => $this->openedAt,
            'completed_at' => $this->completedAt,
            'due_at' => $this->dueAt,
            'approval_status' => $this->approvalStatus,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
