<?php

namespace App\Modules\Sdk\Application\Data;

readonly class NavigationItem implements \JsonSerializable
{
    public function __construct(
        public string $itemKey,
        public string $label,
        public string $itemType,
        public array $route,
        public array $badge,
        public int $sortOrder,
        public ?string $requiredPermission,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            itemKey: (string) ($data['item_key'] ?? $data['itemKey'] ?? ''),
            label: (string) ($data['label'] ?? $data['label'] ?? ''),
            itemType: (string) ($data['item_type'] ?? $data['itemType'] ?? ''),
            route: is_array($data['route'] ?? $data['route'] ?? null) ? ($data['route'] ?? $data['route']) : [],
            badge: is_array($data['badge'] ?? $data['badge'] ?? null) ? ($data['badge'] ?? $data['badge']) : [],
            sortOrder: (int) ($data['sort_order'] ?? $data['sortOrder'] ?? 0),
            requiredPermission: isset($data['required_permission']) ? (string) $data['required_permission'] : (isset($data['requiredPermission']) ? (string) $data['requiredPermission'] : null),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'item_key' => $this->itemKey,
            'label' => $this->label,
            'item_type' => $this->itemType,
            'route' => $this->route,
            'badge' => $this->badge,
            'sort_order' => $this->sortOrder,
            'required_permission' => $this->requiredPermission,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
