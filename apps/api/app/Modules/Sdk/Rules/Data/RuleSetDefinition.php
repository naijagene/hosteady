<?php

namespace App\Modules\Sdk\Rules\Data;

readonly class RuleSetDefinition implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $name,
        public ?string $description,
        public string $scope = 'organization',
        public string $status = 'draft',
        public ?string $moduleKey,
        public array $metadata = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            name: (string) ($data['name'] ?? $data['name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : (isset($data['description']) ? (string) $data['description'] : null),
            scope: (string) ($data['scope'] ?? $data['scope'] ?? ''),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : (isset($data['moduleKey']) ? (string) $data['moduleKey'] : null),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'name' => $this->name,
            'description' => $this->description,
            'scope' => $this->scope,
            'status' => $this->status,
            'module_key' => $this->moduleKey,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
