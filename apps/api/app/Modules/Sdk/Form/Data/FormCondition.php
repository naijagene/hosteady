<?php

namespace App\Modules\Sdk\Form\Data;

readonly class FormCondition implements \JsonSerializable
{
    public function __construct(
        public string $key,
        public string $field,
        public string $operator,
        public mixed $value = null,
        public ?string $targetType = null,
        public ?string $targetKey = null,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            field: (string) ($data['field'] ?? ''),
            operator: (string) ($data['operator'] ?? 'equals'),
            value: $data['value'] ?? null,
            targetType: isset($data['target_type']) ? (string) $data['target_type'] : null,
            targetKey: isset($data['target_key']) ? (string) $data['target_key'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'field' => $this->field,
            'operator' => $this->operator,
            'value' => $this->value,
            'target_type' => $this->targetType,
            'target_key' => $this->targetKey,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
