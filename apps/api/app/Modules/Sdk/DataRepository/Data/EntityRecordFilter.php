<?php

namespace App\Modules\Sdk\DataRepository\Data;

readonly class EntityRecordFilter implements \JsonSerializable
{
    public function __construct(
        public string $field,
        public string $operator,
        public mixed $value = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            field: (string) ($data['field'] ?? ''),
            operator: (string) ($data['operator'] ?? 'eq'),
            value: $data['value'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'operator' => $this->operator,
            'value' => $this->value,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
