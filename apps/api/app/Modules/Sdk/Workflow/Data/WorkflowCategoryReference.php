<?php

namespace App\Modules\Sdk\Workflow\Data;

readonly class WorkflowCategoryReference implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $publicId,
        public string $categoryKey,
        public string $name,
        public ?string $description = null,
        public ?string $moduleKey = null,
        public array $metadata = [],
        public ?string $createdAt = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'category_key' => $this->categoryKey,
            'name' => $this->name,
            'description' => $this->description,
            'module_key' => $this->moduleKey,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
