<?php

namespace App\Modules\Sdk\Theme\Data;

readonly class BrandProfile implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public ?string $themeDefinitionPublicId,
        public string $name,
        public ?string $logoUrl,
        public array $colors,
        public array $typography,
        public array $assets,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            themeDefinitionPublicId: isset($data['theme_definition_public_id']) ? (string) $data['theme_definition_public_id'] : (isset($data['themeDefinitionPublicId']) ? (string) $data['themeDefinitionPublicId'] : null),
            name: (string) ($data['name'] ?? $data['name'] ?? ''),
            logoUrl: isset($data['logo_url']) ? (string) $data['logo_url'] : (isset($data['logoUrl']) ? (string) $data['logoUrl'] : null),
            colors: is_array($data['colors'] ?? $data['colors'] ?? null) ? ($data['colors'] ?? $data['colors']) : [],
            typography: is_array($data['typography'] ?? $data['typography'] ?? null) ? ($data['typography'] ?? $data['typography']) : [],
            assets: is_array($data['assets'] ?? $data['assets'] ?? null) ? ($data['assets'] ?? $data['assets']) : [],
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'theme_definition_public_id' => $this->themeDefinitionPublicId,
            'name' => $this->name,
            'logo_url' => $this->logoUrl,
            'colors' => $this->colors,
            'typography' => $this->typography,
            'assets' => $this->assets,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
