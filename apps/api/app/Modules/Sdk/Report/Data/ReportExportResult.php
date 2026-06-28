<?php

namespace App\Modules\Sdk\Report\Data;

readonly class ReportExportResult implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $exportFormat,
        public string $status,
        public ?array $fileReference = null,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? ''),
            exportFormat: (string) ($data['export_format'] ?? 'csv'),
            status: (string) ($data['status'] ?? 'pending'),
            fileReference: is_array($data['file_reference'] ?? null) ? $data['file_reference'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'export_format' => $this->exportFormat,
            'status' => $this->status,
            'file_reference' => $this->fileReference,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
