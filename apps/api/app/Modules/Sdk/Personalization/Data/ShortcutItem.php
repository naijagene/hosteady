<?php

namespace App\Modules\Sdk\Personalization\Data;

readonly class ShortcutItem implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $shortcutKey,
        public string $label,
        public ?string $route,
        public ?string $target,
        public bool $isActive,
        public array $metadata,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            shortcutKey: (string) ($data['shortcut_key'] ?? $data['shortcutKey'] ?? ''),
            label: (string) ($data['label'] ?? ''),
            route: isset($data['route']) ? (string) $data['route'] : null,
            target: isset($data['target']) ? (string) $data['target'] : null,
            isActive: (bool) ($data['is_active'] ?? $data['isActive'] ?? true),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'shortcut_key' => $this->shortcutKey,
            'label' => $this->label,
            'route' => $this->route,
            'target' => $this->target,
            'is_active' => $this->isActive,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
