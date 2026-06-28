<?php

namespace App\Modules\Sdk\Application\Data;

readonly class ApplicationDefinition implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $applicationKey,
        public string $name,
        public ?string $description,
        public string $applicationType,
        public string $status,
        public string $visibility,
        public ?string $moduleKey,
        public ?string $catalogApplicationPublicId,
        public array $manifest,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            applicationKey: (string) ($data['application_key'] ?? $data['applicationKey'] ?? ''),
            name: (string) ($data['name'] ?? $data['name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : (isset($data['description']) ? (string) $data['description'] : null),
            applicationType: (string) ($data['application_type'] ?? $data['applicationType'] ?? ''),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            visibility: (string) ($data['visibility'] ?? $data['visibility'] ?? ''),
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : (isset($data['moduleKey']) ? (string) $data['moduleKey'] : null),
            catalogApplicationPublicId: isset($data['catalog_application_public_id']) ? (string) $data['catalog_application_public_id'] : (isset($data['catalogApplicationPublicId']) ? (string) $data['catalogApplicationPublicId'] : null),
            manifest: is_array($data['manifest'] ?? $data['manifest'] ?? null) ? ($data['manifest'] ?? $data['manifest']) : [],
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'application_key' => $this->applicationKey,
            'name' => $this->name,
            'description' => $this->description,
            'application_type' => $this->applicationType,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'module_key' => $this->moduleKey,
            'catalog_application_public_id' => $this->catalogApplicationPublicId,
            'manifest' => $this->manifest,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
