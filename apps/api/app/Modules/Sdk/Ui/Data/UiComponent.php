<?php

namespace App\Modules\Sdk\Ui\Data;

readonly class UiComponent implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $componentKey,
        public string $name,
        public ?string $description,
        public string $componentType,
        public string $status,
        public ?string $bindingType,
        public array $bindingConfig,
        public array $actions,
        public array $conditions,
        public array $metadata,
        public ?string $moduleKey
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            componentKey: (string) ($data['component_key'] ?? $data['componentKey'] ?? ''),
            name: (string) ($data['name'] ?? $data['name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : (isset($data['description']) ? (string) $data['description'] : null),
            componentType: (string) ($data['component_type'] ?? $data['componentType'] ?? ''),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            bindingType: isset($data['binding_type']) ? (string) $data['binding_type'] : (isset($data['bindingType']) ? (string) $data['bindingType'] : null),
            bindingConfig: is_array($data['binding_config'] ?? $data['bindingConfig'] ?? null) ? ($data['binding_config'] ?? $data['bindingConfig']) : [],
            actions: is_array($data['actions'] ?? $data['actions'] ?? null) ? ($data['actions'] ?? $data['actions']) : [],
            conditions: is_array($data['conditions'] ?? $data['conditions'] ?? null) ? ($data['conditions'] ?? $data['conditions']) : [],
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : (isset($data['moduleKey']) ? (string) $data['moduleKey'] : null),
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'component_key' => $this->componentKey,
            'name' => $this->name,
            'description' => $this->description,
            'component_type' => $this->componentType,
            'status' => $this->status,
            'binding_type' => $this->bindingType,
            'binding_config' => $this->bindingConfig,
            'actions' => $this->actions,
            'conditions' => $this->conditions,
            'metadata' => $this->metadata,
            'module_key' => $this->moduleKey,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
