<?php

namespace App\Modules\Sdk\Document\Data;

readonly class DocumentReference implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $title,
        public ?string $description,
        public string $status = 'active',
        public string $visibility = 'organization',
        public string $category = 'general',
        public ?string $moduleKey,
        public ?string $currentVersionPublicId,
        public int $currentVersionNumber = 1,
        public ?string $platformFilePublicId,
        public ?string $organizationId,
        public ?string $workspaceId,
        public string $retentionAction = 'none',
        public array $metadata,
        public ?string $createdAt,
        public ?string $updatedAt
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            title: (string) ($data['title'] ?? $data['title'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : (isset($data['description']) ? (string) $data['description'] : null),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            visibility: (string) ($data['visibility'] ?? $data['visibility'] ?? ''),
            category: (string) ($data['category'] ?? $data['category'] ?? ''),
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : (isset($data['moduleKey']) ? (string) $data['moduleKey'] : null),
            currentVersionPublicId: isset($data['current_version_public_id']) ? (string) $data['current_version_public_id'] : (isset($data['currentVersionPublicId']) ? (string) $data['currentVersionPublicId'] : null),
            currentVersionNumber: (int) ($data['current_version_number'] ?? $data['currentVersionNumber'] ?? 0),
            platformFilePublicId: isset($data['platform_file_public_id']) ? (string) $data['platform_file_public_id'] : (isset($data['platformFilePublicId']) ? (string) $data['platformFilePublicId'] : null),
            organizationId: isset($data['organization_id']) ? (string) $data['organization_id'] : (isset($data['organizationId']) ? (string) $data['organizationId'] : null),
            workspaceId: isset($data['workspace_id']) ? (string) $data['workspace_id'] : (isset($data['workspaceId']) ? (string) $data['workspaceId'] : null),
            retentionAction: (string) ($data['retention_action'] ?? $data['retentionAction'] ?? ''),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : (isset($data['createdAt']) ? (string) $data['createdAt'] : null),
            updatedAt: isset($data['updated_at']) ? (string) $data['updated_at'] : (isset($data['updatedAt']) ? (string) $data['updatedAt'] : null)
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'category' => $this->category,
            'module_key' => $this->moduleKey,
            'current_version_public_id' => $this->currentVersionPublicId,
            'current_version_number' => $this->currentVersionNumber,
            'platform_file_public_id' => $this->platformFilePublicId,
            'organization_id' => $this->organizationId,
            'workspace_id' => $this->workspaceId,
            'retention_action' => $this->retentionAction,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    
}
