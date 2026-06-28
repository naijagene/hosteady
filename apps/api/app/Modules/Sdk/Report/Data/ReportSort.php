<?php

namespace App\Modules\Sdk\Report\Data;

readonly class ReportSort implements \JsonSerializable
{
    public function __construct(
        public string $fieldKey,
        public string $direction = 'asc',
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            fieldKey: (string) ($data['field_key'] ?? $data['key'] ?? ''),
            direction: (string) ($data['direction'] ?? 'asc'),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'field_key' => $this->fieldKey,
            'direction' => $this->direction,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
