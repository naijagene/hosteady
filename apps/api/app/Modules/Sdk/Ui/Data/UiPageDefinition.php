<?php

namespace App\Modules\Sdk\Ui\Data;

readonly class UiPageDefinition implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public ?string $moduleKey,
        public string $pageKey,
        public string $name,
        public ?string $description,
        public string $pageType,
        public string $status,
        public string $visibility,
        public ?string $routePath,
        public ?string $icon,
        public array $layout,
        public array $regions,
        public array $components,
        public array $actions,
        public array $conditions,
        public array $breakpoints,
        public array $theme,
        public array $metadata,
        public ?string $applicationPublicId
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : (isset($data['moduleKey']) ? (string) $data['moduleKey'] : null),
            pageKey: (string) ($data['page_key'] ?? $data['pageKey'] ?? ''),
            name: (string) ($data['name'] ?? $data['name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : (isset($data['description']) ? (string) $data['description'] : null),
            pageType: (string) ($data['page_type'] ?? $data['pageType'] ?? ''),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            visibility: (string) ($data['visibility'] ?? $data['visibility'] ?? ''),
            routePath: isset($data['route_path']) ? (string) $data['route_path'] : (isset($data['routePath']) ? (string) $data['routePath'] : null),
            icon: isset($data['icon']) ? (string) $data['icon'] : (isset($data['icon']) ? (string) $data['icon'] : null),
            layout: is_array($data['layout'] ?? $data['layout'] ?? null) ? ($data['layout'] ?? $data['layout']) : [],
            regions: is_array($data['regions'] ?? $data['regions'] ?? null) ? ($data['regions'] ?? $data['regions']) : [],
            components: is_array($data['components'] ?? $data['components'] ?? null) ? ($data['components'] ?? $data['components']) : [],
            actions: is_array($data['actions'] ?? $data['actions'] ?? null) ? ($data['actions'] ?? $data['actions']) : [],
            conditions: is_array($data['conditions'] ?? $data['conditions'] ?? null) ? ($data['conditions'] ?? $data['conditions']) : [],
            breakpoints: is_array($data['breakpoints'] ?? $data['breakpoints'] ?? null) ? ($data['breakpoints'] ?? $data['breakpoints']) : [],
            theme: is_array($data['theme'] ?? $data['theme'] ?? null) ? ($data['theme'] ?? $data['theme']) : [],
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
            applicationPublicId: isset($data['application_public_id']) ? (string) $data['application_public_id'] : (isset($data['applicationPublicId']) ? (string) $data['applicationPublicId'] : null),
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'module_key' => $this->moduleKey,
            'page_key' => $this->pageKey,
            'name' => $this->name,
            'description' => $this->description,
            'page_type' => $this->pageType,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'route_path' => $this->routePath,
            'icon' => $this->icon,
            'layout' => $this->layout,
            'regions' => $this->regions,
            'components' => $this->components,
            'actions' => $this->actions,
            'conditions' => $this->conditions,
            'breakpoints' => $this->breakpoints,
            'theme' => $this->theme,
            'metadata' => $this->metadata,
            'application_public_id' => $this->applicationPublicId,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
