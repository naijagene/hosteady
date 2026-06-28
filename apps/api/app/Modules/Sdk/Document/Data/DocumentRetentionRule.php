<?php

namespace App\Modules\Sdk\Document\Data;

readonly class DocumentRetentionRule implements \JsonSerializable
{
    public function __construct(
        public string $documentPublicId,
        public string $action = 'none',
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            documentPublicId: (string) ($data['document_public_id'] ?? $data['documentPublicId'] ?? ''),
            action: (string) ($data['action'] ?? $data['action'] ?? ''),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'document_public_id' => $this->documentPublicId,
            'action' => $this->action,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    
}
