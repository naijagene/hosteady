<?php

namespace App\Modules\Sdk\Workflow\Human\Data;

readonly class TaskAssignment implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $publicId,
        public string $assignmentType,
        public ?string $assigneeMembershipPublicId = null,
        public ?string $assigneeUserPublicId = null,
        public ?string $roleKey = null,
        public ?string $assignedByMembershipPublicId = null,
        public ?string $assignedAt = null,
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
            'assignment_type' => $this->assignmentType,
            'assignee_membership_public_id' => $this->assigneeMembershipPublicId,
            'assignee_user_public_id' => $this->assigneeUserPublicId,
            'role_key' => $this->roleKey,
            'assigned_by_membership_public_id' => $this->assignedByMembershipPublicId,
            'assigned_at' => $this->assignedAt,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
