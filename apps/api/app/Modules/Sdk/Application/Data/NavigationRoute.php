<?php

namespace App\Modules\Sdk\Application\Data;

readonly class NavigationRoute implements \JsonSerializable
{
    public function __construct(
        public string $name,
        public string $path,
        public ?string $moduleKey,
        public array $parameters
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? $data['name'] ?? ''),
            path: (string) ($data['path'] ?? $data['path'] ?? ''),
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : (isset($data['moduleKey']) ? (string) $data['moduleKey'] : null),
            parameters: is_array($data['parameters'] ?? $data['parameters'] ?? null) ? ($data['parameters'] ?? $data['parameters']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'path' => $this->path,
            'module_key' => $this->moduleKey,
            'parameters' => $this->parameters,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
