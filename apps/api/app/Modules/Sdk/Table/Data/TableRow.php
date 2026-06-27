<?php

namespace App\Modules\Sdk\Table\Data;

readonly class TableRow implements \JsonSerializable
{
    public function __construct(
        public ?string $publicId = null,
        public array $values = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: isset($data['public_id']) ? (string) $data['public_id'] : null,
            values: is_array($data['values'] ?? null) ? $data['values'] : (is_array($data) ? $data : []),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'values' => $this->values,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
