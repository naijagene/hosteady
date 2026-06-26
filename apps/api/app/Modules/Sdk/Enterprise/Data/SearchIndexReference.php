<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class SearchIndexReference
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $publicId,
        public string $displayName,
        public string $entityType,
        public string $entityPublicId,
        public ?string $moduleKey = null,
        public ?EntityReference $entityReference = null,
        public ?string $visibility = null,
        public array $metadata = [],
        public ?string $keywords = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'display_name' => $this->displayName,
            'entity_type' => $this->entityType,
            'entity_public_id' => $this->entityPublicId,
            'module_key' => $this->moduleKey,
            'entity_reference' => $this->entityReference?->toArray(),
            'visibility' => $this->visibility,
            'metadata' => $this->metadata,
            'keywords' => $this->keywords,
        ];
    }
}
