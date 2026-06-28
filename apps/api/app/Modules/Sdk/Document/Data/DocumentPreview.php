<?php

namespace App\Modules\Sdk\Document\Data;

readonly class DocumentPreview implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $documentPublicId,
        public ?string $versionPublicId,
        public string $status = 'pending',
        public ?string $previewFormat,
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
            previewFormat: isset($data['preview_format']) ? (string) $data['preview_format'] : (isset($data['previewFormat']) ? (string) $data['previewFormat'] : null),
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
            'preview_format' => $this->previewFormat,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    
}
