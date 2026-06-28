<?php

namespace App\Modules\Sdk\DataRepository\Data;

readonly class EntityRecordData implements \JsonSerializable
{
    public function __construct(
        public array $values = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            values: is_array($data['values'] ?? null) ? $data['values'] : (is_array($data) && ! isset($data['values']) ? $data : []),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'values' => $this->values,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
