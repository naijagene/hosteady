<?php

namespace App\Modules\Sdk\Application\Data;

readonly class NavigationMenu implements \JsonSerializable
{
    public function __construct(
        public string $menuKey,
        public string $label,
        public array $groups,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            menuKey: (string) ($data['menu_key'] ?? $data['menuKey'] ?? ''),
            label: (string) ($data['label'] ?? $data['label'] ?? ''),
            groups: is_array($data['groups'] ?? $data['groups'] ?? null) ? ($data['groups'] ?? $data['groups']) : [],
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'menu_key' => $this->menuKey,
            'label' => $this->label,
            'groups' => $this->groups,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
