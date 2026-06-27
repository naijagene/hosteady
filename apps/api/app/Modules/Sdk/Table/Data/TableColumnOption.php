<?php

namespace App\Modules\Sdk\Table\Data;

readonly class TableColumnOption implements \JsonSerializable
{
    public function __construct(
        public string $value,
        public string $label,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            value: (string) ($data['value'] ?? ''),
            label: (string) ($data['label'] ?? $data['name'] ?? $data['value'] ?? ''),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
