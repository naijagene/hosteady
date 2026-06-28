<?php

namespace App\Modules\Sdk\DataRepository\Data;

readonly class EntityRecord implements \JsonSerializable
{
    public function __construct(
        public string $moduleKey,
        public string $entityKey,
        public string $publicId,
        public ?string $organizationId = null,
        public ?string $workspaceId = null,
        public EntityRecordData $recordData = new EntityRecordData,
        public string $status = 'active',
        public string $visibility = 'organization',
        public int $version = 1,
        public ?string $searchText = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $deletedAt = null,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $recordData = $data['record_data'] ?? $data['recordData'] ?? [];

        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            entityKey: (string) ($data['entity_key'] ?? ''),
            publicId: (string) ($data['public_id'] ?? ''),
            organizationId: isset($data['organization_id']) ? (string) $data['organization_id'] : null,
            workspaceId: isset($data['workspace_id']) ? (string) $data['workspace_id'] : null,
            recordData: EntityRecordData::fromArray(is_array($recordData) ? $recordData : []),
            status: (string) ($data['status'] ?? 'active'),
            visibility: (string) ($data['visibility'] ?? 'organization'),
            version: (int) ($data['version'] ?? 1),
            searchText: isset($data['search_text']) ? (string) $data['search_text'] : null,
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string) $data['updated_at'] : null,
            deletedAt: isset($data['deleted_at']) ? (string) $data['deleted_at'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'public_id' => $this->publicId,
            'organization_id' => $this->organizationId,
            'workspace_id' => $this->workspaceId,
            'record_data' => $this->recordData->toArray(),
            'status' => $this->status,
            'visibility' => $this->visibility,
            'version' => $this->version,
            'search_text' => $this->searchText,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
