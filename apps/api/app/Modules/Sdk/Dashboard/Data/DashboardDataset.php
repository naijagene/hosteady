<?php

namespace App\Modules\Sdk\Dashboard\Data;

readonly class DashboardDataset implements \JsonSerializable
{
    public function __construct(
        public string $label,
        public array $data = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            label: (string) ($data['label'] ?? ''),
            data: is_array($data['data'] ?? null) ? $data['data'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'data' => $this->data,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
