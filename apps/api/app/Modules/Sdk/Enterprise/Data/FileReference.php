<?php

namespace App\Modules\Sdk\Enterprise\Data;

use App\Enums\FileCategory;

readonly class FileReference
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $publicId,
        public string $filename,
        public string $originalFilename,
        public string $extension,
        public string $mimeType,
        public int $sizeBytes,
        public string $visibility,
        public string $category,
        public ?string $moduleKey = null,
        public ?EntityReference $entityReference = null,
        public ?string $displayName = null,
        public array $metadata = [],
        public ?string $checksum = null,
        public ?string $uploadedMembershipPublicId = null,
        public ?string $createdAt = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'filename' => $this->filename,
            'original_filename' => $this->originalFilename,
            'extension' => $this->extension,
            'mime_type' => $this->mimeType,
            'size_bytes' => $this->sizeBytes,
            'visibility' => $this->visibility,
            'category' => $this->category,
            'module_key' => $this->moduleKey,
            'entity_reference' => $this->entityReference?->toArray(),
            'display_name' => $this->displayName,
            'metadata' => $this->metadata,
            'checksum' => $this->checksum,
            'uploaded_membership_public_id' => $this->uploadedMembershipPublicId,
            'created_at' => $this->createdAt,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $entityReference = null;

        if (isset($payload['entity_reference']) && is_array($payload['entity_reference'])) {
            $entityReference = EntityReference::fromArray($payload['entity_reference']);
        }

        return new self(
            publicId: (string) $payload['public_id'],
            filename: (string) $payload['filename'],
            originalFilename: (string) ($payload['original_filename'] ?? $payload['filename']),
            extension: (string) $payload['extension'],
            mimeType: (string) $payload['mime_type'],
            sizeBytes: (int) $payload['size_bytes'],
            visibility: (string) $payload['visibility'],
            category: (string) ($payload['category'] ?? FileCategory::Other->value),
            moduleKey: isset($payload['module_key']) ? (string) $payload['module_key'] : null,
            entityReference: $entityReference,
            displayName: isset($payload['display_name']) ? (string) $payload['display_name'] : null,
            metadata: is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
            checksum: isset($payload['checksum']) ? (string) $payload['checksum'] : null,
            uploadedMembershipPublicId: isset($payload['uploaded_membership_public_id'])
                ? (string) $payload['uploaded_membership_public_id']
                : null,
            createdAt: isset($payload['created_at']) ? (string) $payload['created_at'] : null,
        );
    }
}
