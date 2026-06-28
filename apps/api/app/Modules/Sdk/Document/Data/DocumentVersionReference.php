<?php

namespace App\Modules\Sdk\Document\Data;

readonly class DocumentVersionReference implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $documentPublicId,
        public int $versionNumber,
        public string $platformFilePublicId,
        public string $status = 'active',
        public ?string $label,
        public array $metadata,
        public ?string $createdAt
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            documentPublicId: (string) ($data['document_public_id'] ?? $data['documentPublicId'] ?? ''),
            versionNumber: (int) ($data['version_number'] ?? $data['versionNumber'] ?? 0),
            platformFilePublicId: (string) ($data['platform_file_public_id'] ?? $data['platformFilePublicId'] ?? ''),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            label: isset($data['label']) ? (string) $data['label'] : (isset($data['label']) ? (string) $data['label'] : null),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : (isset($data['createdAt']) ? (string) $data['createdAt'] : null)
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'document_public_id' => $this->documentPublicId,
            'version_number' => $this->versionNumber,
            'platform_file_public_id' => $this->platformFilePublicId,
            'status' => $this->status,
            'label' => $this->label,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    
}
