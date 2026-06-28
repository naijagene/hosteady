<?php

namespace App\Modules\Sdk\Report\Data;

readonly class ReportFilter implements \JsonSerializable
{
    public function __construct(
        public string $fieldKey,
        public string $operator,
        public mixed $value = null,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            fieldKey: (string) ($data['field_key'] ?? $data['key'] ?? ''),
            operator: (string) ($data['operator'] ?? 'equals'),
            value: $data['value'] ?? null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'field_key' => $this->fieldKey,
            'operator' => $this->operator,
            'value' => $this->value,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
