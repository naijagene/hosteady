<?php

namespace App\Modules\Sdk\Document\Data;

readonly class DocumentQuotaReport implements \JsonSerializable
{
    public function __construct(
        public int $documentsCount,
        public int $attachmentsCount,
        public int $totalBytes,
        public int $quotaBytes,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            documentsCount: (int) ($data['documents_count'] ?? $data['documentsCount'] ?? 0),
            attachmentsCount: (int) ($data['attachments_count'] ?? $data['attachmentsCount'] ?? 0),
            totalBytes: (int) ($data['total_bytes'] ?? $data['totalBytes'] ?? 0),
            quotaBytes: (int) ($data['quota_bytes'] ?? $data['quotaBytes'] ?? 0),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'documents_count' => $this->documentsCount,
            'attachments_count' => $this->attachmentsCount,
            'total_bytes' => $this->totalBytes,
            'quota_bytes' => $this->quotaBytes,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    
}
