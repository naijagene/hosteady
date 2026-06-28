<?php

namespace App\Modules\Sdk\Report\Data;

readonly class ReportColumn implements \JsonSerializable
{
    public function __construct(
        public string $key,
        public string $label,
        public string $type,
        public bool $sortable = true,
        public bool $filterable = true,
        public bool $visible = true,
        public ?int $width = null,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            label: (string) ($data['label'] ?? $data['name'] ?? ''),
            type: (string) ($data['type'] ?? 'text'),
            sortable: (bool) ($data['sortable'] ?? true),
            filterable: (bool) ($data['filterable'] ?? true),
            visible: (bool) ($data['visible'] ?? true),
            width: isset($data['width']) ? (int) $data['width'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'sortable' => $this->sortable,
            'filterable' => $this->filterable,
            'visible' => $this->visible,
            'width' => $this->width,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
