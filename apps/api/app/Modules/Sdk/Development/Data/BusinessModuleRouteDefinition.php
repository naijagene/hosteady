<?php

namespace App\Modules\Sdk\Development\Data;

readonly class BusinessModuleRouteDefinition implements \JsonSerializable
{
    public function __construct(
        public string $name,
        public string $method,
        public string $uri,
        public string $action,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            method: (string) ($data['method'] ?? 'GET'),
            uri: (string) ($data['uri'] ?? ''),
            action: (string) ($data['action'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'method' => $this->method,
            'uri' => $this->uri,
            'action' => $this->action,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
