<?php

namespace App\Modules\Sdk\Development\Data;

readonly class BusinessModulePermissionDefinition implements \JsonSerializable
{
    public function __construct(
        public string $key,
        public string $name,
        public ?string $description = null,
        public ?string $domain = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            domain: isset($data['domain']) ? (string) $data['domain'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'domain' => $this->domain,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
