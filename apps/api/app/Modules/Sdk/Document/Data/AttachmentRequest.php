<?php

namespace App\Modules\Sdk\Document\Data;

readonly class AttachmentRequest implements \JsonSerializable
{
    public function __construct(
        public string $documentPublicId,
        public string $subjectType,
        public string $subjectPublicId,
        public ?string $subjectModuleKey,
        public ?string $subjectEntityKey,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            documentPublicId: (string) ($data['document_public_id'] ?? $data['documentPublicId'] ?? ''),
            subjectType: (string) ($data['subject_type'] ?? $data['subjectType'] ?? ''),
            subjectPublicId: (string) ($data['subject_public_id'] ?? $data['subjectPublicId'] ?? ''),
            subjectModuleKey: isset($data['subject_module_key']) ? (string) $data['subject_module_key'] : (isset($data['subjectModuleKey']) ? (string) $data['subjectModuleKey'] : null),
            subjectEntityKey: isset($data['subject_entity_key']) ? (string) $data['subject_entity_key'] : (isset($data['subjectEntityKey']) ? (string) $data['subjectEntityKey'] : null),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'document_public_id' => $this->documentPublicId,
            'subject_type' => $this->subjectType,
            'subject_public_id' => $this->subjectPublicId,
            'subject_module_key' => $this->subjectModuleKey,
            'subject_entity_key' => $this->subjectEntityKey,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    
}
