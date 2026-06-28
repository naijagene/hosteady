<?php

namespace App\Modules\Sdk\Ui\Data;

readonly class UiLayout implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $layoutKey,
        public string $name,
        public ?string $description,
        public string $layoutType,
        public string $status,
        public array $regions,
        public array $breakpoints,
        public array $metadata,
        public ?string $moduleKey
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            layoutKey: (string) ($data['layout_key'] ?? $data['layoutKey'] ?? ''),
            name: (string) ($data['name'] ?? $data['name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : (isset($data['description']) ? (string) $data['description'] : null),
            layoutType: (string) ($data['layout_type'] ?? $data['layoutType'] ?? ''),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            regions: is_array($data['regions'] ?? $data['regions'] ?? null) ? ($data['regions'] ?? $data['regions']) : [],
            breakpoints: is_array($data['breakpoints'] ?? $data['breakpoints'] ?? null) ? ($data['breakpoints'] ?? $data['breakpoints']) : [],
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : (isset($data['moduleKey']) ? (string) $data['moduleKey'] : null),
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'layout_key' => $this->layoutKey,
            'name' => $this->name,
            'description' => $this->description,
            'layout_type' => $this->layoutType,
            'status' => $this->status,
            'regions' => $this->regions,
            'breakpoints' => $this->breakpoints,
            'metadata' => $this->metadata,
            'module_key' => $this->moduleKey,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
