<?php

namespace App\Modules\Sdk\Ui\Data;

readonly class UiComponentBinding implements \JsonSerializable
{
    public function __construct(
        public string $bindingType,
        public ?string $publicId,
        public ?string $moduleKey,
        public ?string $resourceKey,
        public array $config
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            bindingType: (string) ($data['binding_type'] ?? $data['bindingType'] ?? ''),
            publicId: isset($data['public_id']) ? (string) $data['public_id'] : (isset($data['publicId']) ? (string) $data['publicId'] : null),
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : (isset($data['moduleKey']) ? (string) $data['moduleKey'] : null),
            resourceKey: isset($data['resource_key']) ? (string) $data['resource_key'] : (isset($data['resourceKey']) ? (string) $data['resourceKey'] : null),
            config: is_array($data['config'] ?? $data['config'] ?? null) ? ($data['config'] ?? $data['config']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'binding_type' => $this->bindingType,
            'public_id' => $this->publicId,
            'module_key' => $this->moduleKey,
            'resource_key' => $this->resourceKey,
            'config' => $this->config,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
