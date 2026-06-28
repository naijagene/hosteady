<?php

namespace App\Modules\Sdk\Report\Data;

readonly class ReportRunReference implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $status,
        public ?string $reportDefinitionId = null,
        public ?string $moduleKey = null,
        public ?string $reportKey = null,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? ''),
            status: (string) ($data['status'] ?? 'pending'),
            reportDefinitionId: isset($data['report_definition_id']) ? (string) $data['report_definition_id'] : null,
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : null,
            reportKey: isset($data['report_key']) ? (string) $data['report_key'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'status' => $this->status,
            'report_definition_id' => $this->reportDefinitionId,
            'module_key' => $this->moduleKey,
            'report_key' => $this->reportKey,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
