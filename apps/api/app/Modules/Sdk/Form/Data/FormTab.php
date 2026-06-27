<?php

namespace App\Modules\Sdk\Form\Data;

readonly class FormTab implements \JsonSerializable
{
    public function __construct(
        public string $key,
        public string $label,
        public ?string $description = null,
        public int $order = 0,
        public ?string $icon = null,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            label: (string) ($data['label'] ?? $data['name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            order: (int) ($data['order'] ?? 0),
            icon: isset($data['icon']) ? (string) $data['icon'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'description' => $this->description,
            'order' => $this->order,
            'icon' => $this->icon,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
