<?php

namespace App\Modules\Sdk\Workflow\Automation\Data;

readonly class WorkflowEventSubscription implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $publicId,
        public string $rulePublicId,
        public string $eventName,
        public string $status,
        public string $organizationPublicId,
        public ?string $workspacePublicId = null,
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
            'event_name' => $this->eventName,
            'status' => $this->status,
            'organization_public_id' => $this->organizationPublicId,
            'workspace_public_id' => $this->workspacePublicId,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
