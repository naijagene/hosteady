<?php

namespace App\Modules\Sdk\Application\Data;

readonly class ApplicationWorkspace implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $workspaceKey,
        public string $name,
        public string $status,
        public string $applicationPublicId,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            workspaceKey: (string) ($data['workspace_key'] ?? $data['workspaceKey'] ?? ''),
            name: (string) ($data['name'] ?? $data['name'] ?? ''),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            applicationPublicId: (string) ($data['application_public_id'] ?? $data['applicationPublicId'] ?? ''),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'workspace_key' => $this->workspaceKey,
            'name' => $this->name,
            'status' => $this->status,
            'application_public_id' => $this->applicationPublicId,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
