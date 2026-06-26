<?php

namespace App\Modules\Sdk\Workflow\Runtime\Data;

use App\Modules\Sdk\Enterprise\Data\EntityReference;

readonly class WorkflowExecutionContext implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $organizationPublicId,
        public ?string $workspacePublicId = null,
        public ?string $userPublicId = null,
        public ?string $membershipPublicId = null,
        public ?string $moduleKey = null,
        public ?EntityReference $entityReference = null,
        public array $metadata = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $entityReference = null;
        if (is_array($payload['entity_reference'] ?? null)) {
            $entityReference = EntityReference::fromArray($payload['entity_reference']);
        }

        return new self(
            organizationPublicId: (string) $payload['organization_public_id'],
            workspacePublicId: isset($payload['workspace_public_id']) ? (string) $payload['workspace_public_id'] : null,
            userPublicId: isset($payload['user_public_id']) ? (string) $payload['user_public_id'] : null,
            membershipPublicId: isset($payload['membership_public_id']) ? (string) $payload['membership_public_id'] : null,
            moduleKey: isset($payload['module_key']) ? (string) $payload['module_key'] : null,
            entityReference: $entityReference,
            metadata: is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'organization_public_id' => $this->organizationPublicId,
            'workspace_public_id' => $this->workspacePublicId,
            'user_public_id' => $this->userPublicId,
            'membership_public_id' => $this->membershipPublicId,
            'module_key' => $this->moduleKey,
            'entity_reference' => $this->entityReference?->toArray(),
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
