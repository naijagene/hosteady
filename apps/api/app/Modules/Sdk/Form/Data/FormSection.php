<?php

namespace App\Modules\Sdk\Form\Data;

readonly class FormSection implements \JsonSerializable
{
    public function __construct(
        public string $key,
        public string $label,
        public ?string $description = null,
        public ?string $tabKey = null,
        public int $order = 0,
        public bool $collapsible = false,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            label: (string) ($data['label'] ?? $data['name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            tabKey: isset($data['tab_key']) ? (string) $data['tab_key'] : null,
            order: (int) ($data['order'] ?? 0),
            collapsible: (bool) ($data['collapsible'] ?? false),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'description' => $this->description,
            'tab_key' => $this->tabKey,
            'order' => $this->order,
            'collapsible' => $this->collapsible,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
