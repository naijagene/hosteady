<?php

namespace App\Modules\Sdk\Rules\Data;

readonly class RuleCondition implements \JsonSerializable
{
    public function __construct(
        public string $field,
        public string $operator,
        public mixed $value,
        public array $metadata = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            field: (string) ($data['field'] ?? $data['field'] ?? ''),
            operator: (string) ($data['operator'] ?? $data['operator'] ?? ''),
            value: $data['value'] ?? $data['value'] ?? null,
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'operator' => $this->operator,
            'value' => $this->value,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
