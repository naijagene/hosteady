<?php

namespace App\Modules\Sdk\Report\Data;

readonly class ReportChart implements \JsonSerializable
{
    public function __construct(
        public string $key,
        public string $name,
        public string $chartType,
        public ?string $dataSourceType = null,
        public array $dataSourceConfig = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? $data['chart_key'] ?? ''),
            name: (string) ($data['name'] ?? $data['label'] ?? ''),
            chartType: (string) ($data['chart_type'] ?? 'bar'),
            dataSourceType: isset($data['data_source_type']) ? (string) $data['data_source_type'] : null,
            dataSourceConfig: is_array($data['data_source_config'] ?? null) ? $data['data_source_config'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'chart_type' => $this->chartType,
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
