<?php

namespace App\Modules\Sdk\Rules\Data;

readonly class RuleContext implements \JsonSerializable
{
    public function __construct(
        public string $organizationId,
        public ?string $workspaceId,
        public ?string $moduleKey,
        public ?string $entityKey,
        public string $triggerType = 'manual',
        public ?string $subjectPublicId,
        public array $facts = [],
        public array $metadata = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            organizationId: (string) ($data['organization_id'] ?? $data['organizationId'] ?? ''),
            workspaceId: isset($data['workspace_id']) ? (string) $data['workspace_id'] : (isset($data['workspaceId']) ? (string) $data['workspaceId'] : null),
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : (isset($data['moduleKey']) ? (string) $data['moduleKey'] : null),
            entityKey: isset($data['entity_key']) ? (string) $data['entity_key'] : (isset($data['entityKey']) ? (string) $data['entityKey'] : null),
            triggerType: (string) ($data['trigger_type'] ?? $data['triggerType'] ?? ''),
            subjectPublicId: isset($data['subject_public_id']) ? (string) $data['subject_public_id'] : (isset($data['subjectPublicId']) ? (string) $data['subjectPublicId'] : null),
            facts: is_array($data['facts'] ?? $data['facts'] ?? null) ? ($data['facts'] ?? $data['facts']) : [],
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'workspace_id' => $this->workspaceId,
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'trigger_type' => $this->triggerType,
            'subject_public_id' => $this->subjectPublicId,
            'facts' => $this->facts,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
