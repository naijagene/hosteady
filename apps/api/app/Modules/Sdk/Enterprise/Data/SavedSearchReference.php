<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class SavedSearchReference
{
    /**
     * @param  array<string, mixed>|null  $filters
     */
    public function __construct(
        public string $publicId,
        public string $name,
        public ?string $query = null,
        public ?array $filters = null,
        public ?string $moduleKey = null,
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
            'name' => $this->name,
            'query' => $this->query,
            'filters' => $this->filters,
            'module_key' => $this->moduleKey,
            'created_at' => $this->createdAt,
        ];
    }
}
