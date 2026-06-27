<?php

namespace App\Modules\Sdk\Table\Data;

readonly class TableReference implements \JsonSerializable
{
    public function __construct(
        public string $moduleKey,
        public string $tableKey,
        public ?string $publicId = null,
        public ?string $entityKey = null,
        public ?string $label = null,
        public ?string $organizationId = null,
        public ?string $workspaceId = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            tableKey: (string) ($data['table_key'] ?? $data['key'] ?? ''),
            publicId: isset($data['public_id']) ? (string) $data['public_id'] : null,
            entityKey: isset($data['entity_key']) ? (string) $data['entity_key'] : null,
            label: isset($data['label']) ? (string) $data['label'] : null,
            organizationId: isset($data['organization_id']) ? (string) $data['organization_id'] : null,
            workspaceId: isset($data['workspace_id']) ? (string) $data['workspace_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'table_key' => $this->tableKey,
            'public_id' => $this->publicId,
            'entity_key' => $this->entityKey,
            'label' => $this->label,
            'organization_id' => $this->organizationId,
            'workspace_id' => $this->workspaceId,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
