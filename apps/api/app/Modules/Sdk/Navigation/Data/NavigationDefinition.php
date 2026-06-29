<?php

namespace App\Modules\Sdk\Navigation\Data;

readonly class NavigationDefinition implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public ?string $moduleKey,
        public string $navigationKey,
        public string $name,
        public ?string $description,
        public string $type,
        public string $status,
        public string $visibility,
        public string $scope,
        public array $structure,
        public array $conditions,
        public array $metadata,
        public ?string $applicationPublicId,
        public ?string $currentVersionPublicId
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : (isset($data['moduleKey']) ? (string) $data['moduleKey'] : null),
            navigationKey: (string) ($data['navigation_key'] ?? $data['navigationKey'] ?? ''),
            name: (string) ($data['name'] ?? $data['name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : (isset($data['description']) ? (string) $data['description'] : null),
            type: (string) ($data['type'] ?? $data['type'] ?? ''),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            visibility: (string) ($data['visibility'] ?? $data['visibility'] ?? ''),
            scope: (string) ($data['scope'] ?? $data['scope'] ?? ''),
            structure: is_array($data['structure'] ?? $data['structure'] ?? null) ? ($data['structure'] ?? $data['structure']) : [],
            conditions: is_array($data['conditions'] ?? $data['conditions'] ?? null) ? ($data['conditions'] ?? $data['conditions']) : [],
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
            applicationPublicId: isset($data['application_public_id']) ? (string) $data['application_public_id'] : (isset($data['applicationPublicId']) ? (string) $data['applicationPublicId'] : null),
            currentVersionPublicId: isset($data['current_version_public_id']) ? (string) $data['current_version_public_id'] : (isset($data['currentVersionPublicId']) ? (string) $data['currentVersionPublicId'] : null),
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'module_key' => $this->moduleKey,
            'navigation_key' => $this->navigationKey,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'scope' => $this->scope,
            'structure' => $this->structure,
            'conditions' => $this->conditions,
            'metadata' => $this->metadata,
            'application_public_id' => $this->applicationPublicId,
            'current_version_public_id' => $this->currentVersionPublicId,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
