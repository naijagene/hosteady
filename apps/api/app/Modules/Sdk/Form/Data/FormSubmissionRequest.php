<?php

namespace App\Modules\Sdk\Form\Data;

readonly class FormSubmissionRequest implements \JsonSerializable
{
    public function __construct(
        public string $moduleKey,
        public string $formKey,
        public array $values = [],
        public ?string $entityKey = null,
        public ?string $organizationId = null,
        public ?string $workspaceId = null,
        public ?string $entityPublicId = null,
        public ?string $draftId = null,
        public bool $draft = false,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            formKey: (string) ($data['form_key'] ?? ''),
            values: is_array($data['values'] ?? null) ? $data['values'] : [],
            entityKey: isset($data['entity_key']) ? (string) $data['entity_key'] : null,
            organizationId: isset($data['organization_id']) ? (string) $data['organization_id'] : null,
            workspaceId: isset($data['workspace_id']) ? (string) $data['workspace_id'] : null,
            entityPublicId: isset($data['entity_public_id']) ? (string) $data['entity_public_id'] : null,
            draftId: isset($data['draft_id']) ? (string) $data['draft_id'] : null,
            draft: (bool) ($data['draft'] ?? false),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'form_key' => $this->formKey,
            'values' => $this->values,
            'entity_key' => $this->entityKey,
            'organization_id' => $this->organizationId,
            'workspace_id' => $this->workspaceId,
            'entity_public_id' => $this->entityPublicId,
            'draft_id' => $this->draftId,
            'draft' => $this->draft,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
