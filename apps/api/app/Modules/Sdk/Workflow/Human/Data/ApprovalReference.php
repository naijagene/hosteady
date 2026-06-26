<?php

namespace App\Modules\Sdk\Workflow\Human\Data;

readonly class ApprovalReference implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $publicId,
        public string $status,
        public string $title,
        public ?string $taskPublicId = null,
        public ?string $workflowInstancePublicId = null,
        public ?string $workflowDefinitionName = null,
        public ?string $requestedAt = null,
        public ?string $decidedAt = null,
        public ?string $decidedByMembershipPublicId = null,
        public ?string $decisionType = null,
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
            'status' => $this->status,
            'title' => $this->title,
            'task_public_id' => $this->taskPublicId,
            'workflow_instance_public_id' => $this->workflowInstancePublicId,
            'workflow_definition_name' => $this->workflowDefinitionName,
            'requested_at' => $this->requestedAt,
            'decided_at' => $this->decidedAt,
            'decided_by_membership_public_id' => $this->decidedByMembershipPublicId,
            'decision_type' => $this->decisionType,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
