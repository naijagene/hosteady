<?php

namespace App\Modules\Sdk\Application\Data;

readonly class NavigationGroup implements \JsonSerializable
{
    public function __construct(
        public string $groupKey,
        public string $label,
        public int $sortOrder,
        public array $items,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            groupKey: (string) ($data['group_key'] ?? $data['groupKey'] ?? ''),
            label: (string) ($data['label'] ?? $data['label'] ?? ''),
            sortOrder: (int) ($data['sort_order'] ?? $data['sortOrder'] ?? 0),
            items: is_array($data['items'] ?? $data['items'] ?? null) ? ($data['items'] ?? $data['items']) : [],
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'group_key' => $this->groupKey,
            'label' => $this->label,
            'sort_order' => $this->sortOrder,
            'items' => $this->items,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
