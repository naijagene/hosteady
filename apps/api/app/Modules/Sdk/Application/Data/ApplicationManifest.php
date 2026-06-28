<?php

namespace App\Modules\Sdk\Application\Data;

readonly class ApplicationManifest implements \JsonSerializable
{
    public function __construct(
        public string $applicationKey,
        public string $name,
        public string $version,
        public string $type,
        public array $capabilities,
        public array $dependencies,
        public array $navigation,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            applicationKey: (string) ($data['application_key'] ?? $data['applicationKey'] ?? ''),
            name: (string) ($data['name'] ?? $data['name'] ?? ''),
            version: (string) ($data['version'] ?? $data['version'] ?? ''),
            type: (string) ($data['type'] ?? $data['type'] ?? ''),
            capabilities: is_array($data['capabilities'] ?? $data['capabilities'] ?? null) ? ($data['capabilities'] ?? $data['capabilities']) : [],
            dependencies: is_array($data['dependencies'] ?? $data['dependencies'] ?? null) ? ($data['dependencies'] ?? $data['dependencies']) : [],
            navigation: is_array($data['navigation'] ?? $data['navigation'] ?? null) ? ($data['navigation'] ?? $data['navigation']) : [],
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'application_key' => $this->applicationKey,
            'name' => $this->name,
            'version' => $this->version,
            'type' => $this->type,
            'capabilities' => $this->capabilities,
            'dependencies' => $this->dependencies,
            'navigation' => $this->navigation,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
