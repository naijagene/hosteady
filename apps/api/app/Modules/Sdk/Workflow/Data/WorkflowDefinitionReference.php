<?php

namespace App\Modules\Sdk\Workflow\Data;

readonly class WorkflowDefinitionReference implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $publicId,
        public string $workflowKey,
        public string $name,
        public string $status,
        public ?string $description = null,
        public ?string $moduleKey = null,
        public ?string $categoryPublicId = null,
        public ?string $currentVersionPublicId = null,
        public ?WorkflowVersionData $currentVersion = null,
        public array $metadata = [],
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            publicId: (string) $payload['public_id'],
            workflowKey: (string) $payload['workflow_key'],
            name: (string) $payload['name'],
            status: (string) $payload['status'],
            description: isset($payload['description']) ? (string) $payload['description'] : null,
            moduleKey: isset($payload['module_key']) ? (string) $payload['module_key'] : null,
            categoryPublicId: isset($payload['category_public_id']) ? (string) $payload['category_public_id'] : null,
            currentVersionPublicId: isset($payload['current_version_public_id']) ? (string) $payload['current_version_public_id'] : null,
            currentVersion: isset($payload['current_version']) && is_array($payload['current_version'])
                ? WorkflowVersionData::fromArray($payload['current_version'])
                : null,
            metadata: is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
            createdAt: isset($payload['created_at']) ? (string) $payload['created_at'] : null,
            updatedAt: isset($payload['updated_at']) ? (string) $payload['updated_at'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'workflow_key' => $this->workflowKey,
            'name' => $this->name,
            'status' => $this->status,
            'description' => $this->description,
            'module_key' => $this->moduleKey,
            'category_public_id' => $this->categoryPublicId,
            'current_version_public_id' => $this->currentVersionPublicId,
            'current_version' => $this->currentVersion?->toArray(),
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
