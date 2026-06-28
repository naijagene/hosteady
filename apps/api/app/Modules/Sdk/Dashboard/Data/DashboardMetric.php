<?php

namespace App\Modules\Sdk\Dashboard\Data;

readonly class DashboardMetric implements \JsonSerializable
{
    public function __construct(
        public string $key,
        public string $label,
        public string $format = 'number',
        public ?string $dataSourceType = null,
        public array $dataSourceConfig = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            label: (string) ($data['label'] ?? ''),
            format: (string) ($data['format'] ?? 'number'),
            dataSourceType: isset($data['data_source_type']) ? (string) $data['data_source_type'] : null,
            dataSourceConfig: is_array($data['data_source_config'] ?? null) ? $data['data_source_config'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'format' => $this->format,
            'data_source_type' => $this->dataSourceType,
            'data_source_config' => $this->dataSourceConfig,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
