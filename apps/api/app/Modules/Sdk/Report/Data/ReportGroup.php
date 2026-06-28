<?php

namespace App\Modules\Sdk\Report\Data;

readonly class ReportGroup implements \JsonSerializable
{
    public function __construct(
        public string $fieldKey,
        public ?string $label = null,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            fieldKey: (string) ($data['field_key'] ?? $data['key'] ?? ''),
            label: isset($data['label']) ? (string) $data['label'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'field_key' => $this->fieldKey,
            'label' => $this->label,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
