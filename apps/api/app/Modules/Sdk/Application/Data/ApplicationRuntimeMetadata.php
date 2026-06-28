<?php

namespace App\Modules\Sdk\Application\Data;

readonly class ApplicationRuntimeMetadata implements \JsonSerializable
{
    public function __construct(
        public string $applicationKey,
        public bool $enabled,
        public array $capabilities,
        public array $navigation,
        public array $menus,
        public array $workspace,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            applicationKey: (string) ($data['application_key'] ?? $data['applicationKey'] ?? ''),
            enabled: (bool) ($data['enabled'] ?? $data['enabled'] ?? false),
            capabilities: is_array($data['capabilities'] ?? $data['capabilities'] ?? null) ? ($data['capabilities'] ?? $data['capabilities']) : [],
            navigation: is_array($data['navigation'] ?? $data['navigation'] ?? null) ? ($data['navigation'] ?? $data['navigation']) : [],
            menus: is_array($data['menus'] ?? $data['menus'] ?? null) ? ($data['menus'] ?? $data['menus']) : [],
            workspace: is_array($data['workspace'] ?? $data['workspace'] ?? null) ? ($data['workspace'] ?? $data['workspace']) : [],
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'application_key' => $this->applicationKey,
            'enabled' => $this->enabled,
            'capabilities' => $this->capabilities,
            'navigation' => $this->navigation,
            'menus' => $this->menus,
            'workspace' => $this->workspace,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
