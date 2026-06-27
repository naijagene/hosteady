<?php

namespace App\Modules\Sdk\Table\Data;

readonly class TableSort implements \JsonSerializable
{
    public function __construct(
        public string $columnKey,
        public string $direction = 'asc',
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            columnKey: (string) ($data['column_key'] ?? $data['key'] ?? ''),
            direction: (string) ($data['direction'] ?? 'asc'),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'column_key' => $this->columnKey,
            'direction' => $this->direction,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
