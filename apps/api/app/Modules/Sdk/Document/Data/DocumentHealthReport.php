<?php

namespace App\Modules\Sdk\Document\Data;

readonly class DocumentHealthReport implements \JsonSerializable
{
    public function __construct(
        public bool $enabled,
        public int $documents,
        public int $versions,
        public int $attachments,
        public array $warnings,
        public string $status = 'healthy',
        public array $missingTables
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: (bool) ($data['enabled'] ?? $data['enabled'] ?? false),
            documents: (int) ($data['documents'] ?? $data['documents'] ?? 0),
            versions: (int) ($data['versions'] ?? $data['versions'] ?? 0),
            attachments: (int) ($data['attachments'] ?? $data['attachments'] ?? 0),
            warnings: is_array($data['warnings'] ?? $data['warnings'] ?? null) ? ($data['warnings'] ?? $data['warnings']) : [],
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            missingTables: is_array($data['missing_tables'] ?? $data['missingTables'] ?? null) ? ($data['missing_tables'] ?? $data['missingTables']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'documents' => $this->documents,
            'versions' => $this->versions,
            'attachments' => $this->attachments,
            'warnings' => $this->warnings,
            'status' => $this->status,
            'missing_tables' => $this->missingTables
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    
}
