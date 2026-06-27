<?php

namespace App\Modules\Sdk\Table\Data;

readonly class TableFilter implements \JsonSerializable
{
    public function __construct(
        public string $columnKey,
        public string $operator,
        public mixed $value = null,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            columnKey: (string) ($data['column_key'] ?? $data['key'] ?? ''),
            operator: (string) ($data['operator'] ?? 'equals'),
            value: $data['value'] ?? null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'column_key' => $this->columnKey,
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
