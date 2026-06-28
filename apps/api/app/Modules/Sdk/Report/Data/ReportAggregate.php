<?php

namespace App\Modules\Sdk\Report\Data;

readonly class ReportAggregate implements \JsonSerializable
{
    public function __construct(
        public string $key,
        public string $function,
        public ?string $fieldKey = null,
        public ?string $label = null,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            function: (string) ($data['function'] ?? $data['aggregate_function'] ?? 'count'),
            fieldKey: isset($data['field_key']) ? (string) $data['field_key'] : null,
            label: isset($data['label']) ? (string) $data['label'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'function' => $this->function,
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
