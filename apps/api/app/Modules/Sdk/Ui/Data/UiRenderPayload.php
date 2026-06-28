<?php

namespace App\Modules\Sdk\Ui\Data;

readonly class UiRenderPayload implements \JsonSerializable
{
    public function __construct(
        public array $page,
        public array $layout,
        public array $regions,
        public array $components,
        public array $actions,
        public array $conditions,
        public array $breakpoints,
        public array $theme,
        public array $personalization,
        public array $permissions,
        public array $runtimeContext
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            page: is_array($data['page'] ?? $data['page'] ?? null) ? ($data['page'] ?? $data['page']) : [],
            layout: is_array($data['layout'] ?? $data['layout'] ?? null) ? ($data['layout'] ?? $data['layout']) : [],
            regions: is_array($data['regions'] ?? $data['regions'] ?? null) ? ($data['regions'] ?? $data['regions']) : [],
            components: is_array($data['components'] ?? $data['components'] ?? null) ? ($data['components'] ?? $data['components']) : [],
            actions: is_array($data['actions'] ?? $data['actions'] ?? null) ? ($data['actions'] ?? $data['actions']) : [],
            conditions: is_array($data['conditions'] ?? $data['conditions'] ?? null) ? ($data['conditions'] ?? $data['conditions']) : [],
            breakpoints: is_array($data['breakpoints'] ?? $data['breakpoints'] ?? null) ? ($data['breakpoints'] ?? $data['breakpoints']) : [],
            theme: is_array($data['theme'] ?? $data['theme'] ?? null) ? ($data['theme'] ?? $data['theme']) : [],
            personalization: is_array($data['personalization'] ?? $data['personalization'] ?? null) ? ($data['personalization'] ?? $data['personalization']) : [],
            permissions: is_array($data['permissions'] ?? $data['permissions'] ?? null) ? ($data['permissions'] ?? $data['permissions']) : [],
            runtimeContext: is_array($data['runtime_context'] ?? $data['runtimeContext'] ?? null) ? ($data['runtime_context'] ?? $data['runtimeContext']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'layout' => $this->layout,
            'regions' => $this->regions,
            'components' => $this->components,
            'actions' => $this->actions,
            'conditions' => $this->conditions,
            'breakpoints' => $this->breakpoints,
            'theme' => $this->theme,
            'personalization' => $this->personalization,
            'permissions' => $this->permissions,
            'runtime_context' => $this->runtimeContext,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
