<?php

namespace App\Modules\Sdk\Document\Data;

readonly class DocumentUpdateRequest implements \JsonSerializable
{
    public function __construct(
        public string $documentPublicId,
        public ?string $title,
        public ?string $description,
        public ?string $status,
        public ?string $visibility,
        public ?string $category,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            documentPublicId: (string) ($data['document_public_id'] ?? $data['documentPublicId'] ?? ''),
            title: isset($data['title']) ? (string) $data['title'] : (isset($data['title']) ? (string) $data['title'] : null),
            description: isset($data['description']) ? (string) $data['description'] : (isset($data['description']) ? (string) $data['description'] : null),
            status: isset($data['status']) ? (string) $data['status'] : (isset($data['status']) ? (string) $data['status'] : null),
            visibility: isset($data['visibility']) ? (string) $data['visibility'] : (isset($data['visibility']) ? (string) $data['visibility'] : null),
            category: isset($data['category']) ? (string) $data['category'] : (isset($data['category']) ? (string) $data['category'] : null),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'document_public_id' => $this->documentPublicId,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'category' => $this->category,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    
}
