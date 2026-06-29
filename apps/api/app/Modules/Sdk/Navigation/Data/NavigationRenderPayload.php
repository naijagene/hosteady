<?php

namespace App\Modules\Sdk\Navigation\Data;

readonly class NavigationRenderPayload implements \JsonSerializable
{
    public function __construct(
        public array $definition,
        public array $version,
        public array $tree,
        public array $items,
        public array $permissions,
        public array $personalization,
        public array $runtimeContext,
        public array $warnings
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            definition: is_array($data['definition'] ?? $data['definition'] ?? null) ? ($data['definition'] ?? $data['definition']) : [],
            version: is_array($data['version'] ?? $data['version'] ?? null) ? ($data['version'] ?? $data['version']) : [],
            tree: is_array($data['tree'] ?? $data['tree'] ?? null) ? ($data['tree'] ?? $data['tree']) : [],
            items: is_array($data['items'] ?? $data['items'] ?? null) ? ($data['items'] ?? $data['items']) : [],
            permissions: is_array($data['permissions'] ?? $data['permissions'] ?? null) ? ($data['permissions'] ?? $data['permissions']) : [],
            personalization: is_array($data['personalization'] ?? $data['personalization'] ?? null) ? ($data['personalization'] ?? $data['personalization']) : [],
            runtimeContext: is_array($data['runtime_context'] ?? $data['runtimeContext'] ?? null) ? ($data['runtime_context'] ?? $data['runtimeContext']) : [],
            warnings: is_array($data['warnings'] ?? $data['warnings'] ?? null) ? ($data['warnings'] ?? $data['warnings']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'definition' => $this->definition,
            'version' => $this->version,
            'tree' => $this->tree,
            'items' => $this->items,
            'permissions' => $this->permissions,
            'personalization' => $this->personalization,
            'runtime_context' => $this->runtimeContext,
            'warnings' => $this->warnings,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
