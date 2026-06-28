<?php

namespace App\Modules\Sdk\Document\Data;

readonly class DocumentThumbnail implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $documentPublicId,
        public ?string $versionPublicId,
        public string $status = 'pending',
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            documentPublicId: (string) ($data['document_public_id'] ?? $data['documentPublicId'] ?? ''),
            versionPublicId: isset($data['version_public_id']) ? (string) $data['version_public_id'] : (isset($data['versionPublicId']) ? (string) $data['versionPublicId'] : null),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'document_public_id' => $this->documentPublicId,
            'version_public_id' => $this->versionPublicId,
            'status' => $this->status,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    
}
