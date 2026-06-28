<?php

namespace App\Modules\Sdk\Ui\Data;

readonly class UiLayoutRegion implements \JsonSerializable
{
    public function __construct(
        public string $regionKey,
        public string $regionType,
        public string $label,
        public int $sortOrder,
        public array $components,
        public array $breakpoints,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            regionKey: (string) ($data['region_key'] ?? $data['regionKey'] ?? ''),
            regionType: (string) ($data['region_type'] ?? $data['regionType'] ?? ''),
            label: (string) ($data['label'] ?? $data['label'] ?? ''),
            sortOrder: (int) ($data['sort_order'] ?? $data['sortOrder'] ?? 0),
            components: is_array($data['components'] ?? $data['components'] ?? null) ? ($data['components'] ?? $data['components']) : [],
            breakpoints: is_array($data['breakpoints'] ?? $data['breakpoints'] ?? null) ? ($data['breakpoints'] ?? $data['breakpoints']) : [],
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'region_key' => $this->regionKey,
            'region_type' => $this->regionType,
            'label' => $this->label,
            'sort_order' => $this->sortOrder,
            'components' => $this->components,
            'breakpoints' => $this->breakpoints,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
