<?php

namespace App\Modules\Sdk\Navigation\Data;

readonly class NavigationItem implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public ?string $navigationDefinitionPublicId,
        public ?string $parentItemPublicId,
        public ?string $moduleKey,
        public string $itemKey,
        public string $label,
        public string $itemType,
        public ?string $route,
        public ?string $icon,
        public array $badge,
        public string $visibility,
        public array $conditions,
        public array $permissions,
        public array $roles,
        public int $sortOrder,
        public array $metadata,
        public ?string $applicationPublicId
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            navigationDefinitionPublicId: isset($data['navigation_definition_public_id']) ? (string) $data['navigation_definition_public_id'] : (isset($data['navigationDefinitionPublicId']) ? (string) $data['navigationDefinitionPublicId'] : null),
            parentItemPublicId: isset($data['parent_item_public_id']) ? (string) $data['parent_item_public_id'] : (isset($data['parentItemPublicId']) ? (string) $data['parentItemPublicId'] : null),
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : (isset($data['moduleKey']) ? (string) $data['moduleKey'] : null),
            itemKey: (string) ($data['item_key'] ?? $data['itemKey'] ?? ''),
            label: (string) ($data['label'] ?? $data['label'] ?? ''),
            itemType: (string) ($data['item_type'] ?? $data['itemType'] ?? ''),
            route: isset($data['route']) ? (string) $data['route'] : (isset($data['route']) ? (string) $data['route'] : null),
            icon: isset($data['icon']) ? (string) $data['icon'] : (isset($data['icon']) ? (string) $data['icon'] : null),
            badge: is_array($data['badge'] ?? $data['badge'] ?? null) ? ($data['badge'] ?? $data['badge']) : [],
            visibility: (string) ($data['visibility'] ?? $data['visibility'] ?? ''),
            conditions: is_array($data['conditions'] ?? $data['conditions'] ?? null) ? ($data['conditions'] ?? $data['conditions']) : [],
            permissions: is_array($data['permissions'] ?? $data['permissions'] ?? null) ? ($data['permissions'] ?? $data['permissions']) : [],
            roles: is_array($data['roles'] ?? $data['roles'] ?? null) ? ($data['roles'] ?? $data['roles']) : [],
            sortOrder: (int) ($data['sort_order'] ?? $data['sortOrder'] ?? 0),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
            applicationPublicId: isset($data['application_public_id']) ? (string) $data['application_public_id'] : (isset($data['applicationPublicId']) ? (string) $data['applicationPublicId'] : null),
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'navigation_definition_public_id' => $this->navigationDefinitionPublicId,
            'parent_item_public_id' => $this->parentItemPublicId,
            'module_key' => $this->moduleKey,
            'item_key' => $this->itemKey,
            'label' => $this->label,
            'item_type' => $this->itemType,
            'route' => $this->route,
            'icon' => $this->icon,
            'badge' => $this->badge,
            'visibility' => $this->visibility,
            'conditions' => $this->conditions,
            'permissions' => $this->permissions,
            'roles' => $this->roles,
            'sort_order' => $this->sortOrder,
            'metadata' => $this->metadata,
            'application_public_id' => $this->applicationPublicId,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
