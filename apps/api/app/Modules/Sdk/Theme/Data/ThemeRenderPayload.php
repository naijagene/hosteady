<?php

namespace App\Modules\Sdk\Theme\Data;

readonly class ThemeRenderPayload implements \JsonSerializable
{
    public function __construct(
        public array $definition,
        public array $version,
        public array $brandProfile,
        public array $theme,
        public array $permissions,
        public array $runtimeContext,
        public array $warnings
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            definition: is_array($data['definition'] ?? $data['definition'] ?? null) ? ($data['definition'] ?? $data['definition']) : [],
            version: is_array($data['version'] ?? $data['version'] ?? null) ? ($data['version'] ?? $data['version']) : [],
            brandProfile: is_array($data['brand_profile'] ?? $data['brandProfile'] ?? null) ? ($data['brand_profile'] ?? $data['brandProfile']) : [],
            theme: is_array($data['theme'] ?? $data['theme'] ?? null) ? ($data['theme'] ?? $data['theme']) : [],
            permissions: is_array($data['permissions'] ?? $data['permissions'] ?? null) ? ($data['permissions'] ?? $data['permissions']) : [],
            runtimeContext: is_array($data['runtime_context'] ?? $data['runtimeContext'] ?? null) ? ($data['runtime_context'] ?? $data['runtimeContext']) : [],
            warnings: is_array($data['warnings'] ?? $data['warnings'] ?? null) ? ($data['warnings'] ?? $data['warnings']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'definition' => $this->definition,
            'version' => $this->version,
            'brand_profile' => $this->brandProfile,
            'theme' => $this->theme,
            'permissions' => $this->permissions,
            'runtime_context' => $this->runtimeContext,
            'warnings' => $this->warnings,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
