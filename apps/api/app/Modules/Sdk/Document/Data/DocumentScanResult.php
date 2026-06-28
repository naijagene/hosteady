<?php

namespace App\Modules\Sdk\Document\Data;

readonly class DocumentScanResult implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $documentPublicId,
        public string $status = 'pending',
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            documentPublicId: (string) ($data['document_public_id'] ?? $data['documentPublicId'] ?? ''),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'document_public_id' => $this->documentPublicId,
            'status' => $this->status,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    
}
