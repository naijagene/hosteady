<?php

namespace App\Modules\Sdk\Report\Data;

readonly class ReportExportDefinition implements \JsonSerializable
{
    public function __construct(
        public string $exportFormat,
        public array $parameters = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            exportFormat: (string) ($data['export_format'] ?? $data['format'] ?? 'csv'),
            parameters: is_array($data['parameters'] ?? null) ? $data['parameters'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'export_format' => $this->exportFormat,
            'parameters' => $this->parameters,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
