<?php

namespace App\Modules\Sdk\Dashboard\Data;

readonly class DashboardAction implements \JsonSerializable
{
    public function __construct(
        public string $key,
        public string $label,
        public string $type = 'toolbar',
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            label: (string) ($data['label'] ?? ''),
            type: (string) ($data['type'] ?? 'toolbar'),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
