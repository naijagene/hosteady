<?php

namespace App\Modules\Sdk\Document\Data;

readonly class AttachmentReference implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $documentPublicId,
        public string $subjectType,
        public string $subjectPublicId,
        public ?string $subjectModuleKey,
        public ?string $subjectEntityKey,
        public string $status = 'active',
        public array $metadata,
        public ?string $createdAt
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            documentPublicId: (string) ($data['document_public_id'] ?? $data['documentPublicId'] ?? ''),
            subjectType: (string) ($data['subject_type'] ?? $data['subjectType'] ?? ''),
            subjectPublicId: (string) ($data['subject_public_id'] ?? $data['subjectPublicId'] ?? ''),
            subjectModuleKey: isset($data['subject_module_key']) ? (string) $data['subject_module_key'] : (isset($data['subjectModuleKey']) ? (string) $data['subjectModuleKey'] : null),
            subjectEntityKey: isset($data['subject_entity_key']) ? (string) $data['subject_entity_key'] : (isset($data['subjectEntityKey']) ? (string) $data['subjectEntityKey'] : null),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : (isset($data['createdAt']) ? (string) $data['createdAt'] : null)
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'document_public_id' => $this->documentPublicId,
            'subject_type' => $this->subjectType,
            'subject_public_id' => $this->subjectPublicId,
            'subject_module_key' => $this->subjectModuleKey,
            'subject_entity_key' => $this->subjectEntityKey,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    
}
