<?php

namespace App\Modules\Sdk\Document\Data;

readonly class DocumentVersionRequest implements \JsonSerializable
{
    public function __construct(
        public string $documentPublicId,
        public string $originalFilename,
        public string $mimeType,
        public int $sizeBytes,
        public string $contents,
        public ?string $label = null,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            documentPublicId: (string) ($data['document_public_id'] ?? $data['documentPublicId'] ?? ''),
            originalFilename: (string) ($data['original_filename'] ?? $data['originalFilename'] ?? ''),
            mimeType: (string) ($data['mime_type'] ?? $data['mimeType'] ?? ''),
            sizeBytes: (int) ($data['size_bytes'] ?? $data['sizeBytes'] ?? 0),
            contents: (string) ($data['contents'] ?? $data['contents'] ?? ''),
            label: isset($data['label']) ? (string) $data['label'] : (isset($data['label']) ? (string) $data['label'] : null),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'document_public_id' => $this->documentPublicId,
            'original_filename' => $this->originalFilename,
            'mime_type' => $this->mimeType,
            'size_bytes' => $this->sizeBytes,
            'contents' => $this->contents,
            'label' => $this->label,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    
}
