<?php

namespace App\Modules\Sdk\Document\Data;

readonly class DocumentUploadRequest implements \JsonSerializable
{
    public function __construct(
        public string $title,
        public string $originalFilename,
        public string $mimeType,
        public int $sizeBytes,
        public string $contents,
        public ?string $description,
        public string $visibility = 'organization',
        public string $category = 'general',
        public ?string $moduleKey,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            title: (string) ($data['title'] ?? $data['title'] ?? ''),
            originalFilename: (string) ($data['original_filename'] ?? $data['originalFilename'] ?? ''),
            mimeType: (string) ($data['mime_type'] ?? $data['mimeType'] ?? ''),
            sizeBytes: (int) ($data['size_bytes'] ?? $data['sizeBytes'] ?? 0),
            contents: (string) ($data['contents'] ?? $data['contents'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : (isset($data['description']) ? (string) $data['description'] : null),
            visibility: (string) ($data['visibility'] ?? $data['visibility'] ?? ''),
            category: (string) ($data['category'] ?? $data['category'] ?? ''),
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : (isset($data['moduleKey']) ? (string) $data['moduleKey'] : null),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'original_filename' => $this->originalFilename,
            'mime_type' => $this->mimeType,
            'size_bytes' => $this->sizeBytes,
            'contents' => $this->contents,
            'description' => $this->description,
            'visibility' => $this->visibility,
            'category' => $this->category,
            'module_key' => $this->moduleKey,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    
}
