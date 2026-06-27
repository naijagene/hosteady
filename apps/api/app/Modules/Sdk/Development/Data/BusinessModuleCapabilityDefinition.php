<?php

namespace App\Modules\Sdk\Development\Data;

readonly class BusinessModuleCapabilityDefinition implements \JsonSerializable
{
    public function __construct(
        public string $key,
        public bool $enabled = true,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            enabled: (bool) ($data['enabled'] ?? true),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'enabled' => $this->enabled,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
