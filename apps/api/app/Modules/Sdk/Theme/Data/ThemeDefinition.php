<?php

namespace App\Modules\Sdk\Theme\Data;

readonly class ThemeDefinition implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public ?string $moduleKey,
        public string $themeKey,
        public string $name,
        public ?string $description,
        public string $status,
        public string $scope,
        public string $inheritanceMode,
        public ?string $parentThemePublicId,
        public array $tokens,
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
            themeKey: (string) ($data['theme_key'] ?? $data['themeKey'] ?? ''),
            name: (string) ($data['name'] ?? $data['name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : (isset($data['description']) ? (string) $data['description'] : null),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            scope: (string) ($data['scope'] ?? $data['scope'] ?? ''),
            inheritanceMode: (string) ($data['inheritance_mode'] ?? $data['inheritanceMode'] ?? ''),
            parentThemePublicId: isset($data['parent_theme_public_id']) ? (string) $data['parent_theme_public_id'] : (isset($data['parentThemePublicId']) ? (string) $data['parentThemePublicId'] : null),
            tokens: is_array($data['tokens'] ?? $data['tokens'] ?? null) ? ($data['tokens'] ?? $data['tokens']) : [],
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
            'theme_key' => $this->themeKey,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'scope' => $this->scope,
            'inheritance_mode' => $this->inheritanceMode,
            'parent_theme_public_id' => $this->parentThemePublicId,
            'tokens' => $this->tokens,
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
