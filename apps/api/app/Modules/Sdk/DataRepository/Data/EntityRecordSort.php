<?php

namespace App\Modules\Sdk\DataRepository\Data;

readonly class EntityRecordSort implements \JsonSerializable
{
    public function __construct(
        public string $field,
        public string $direction = 'asc',
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            field: (string) ($data['field'] ?? ''),
            direction: (string) ($data['direction'] ?? 'asc'),
        );
    }

    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'direction' => $this->direction,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
