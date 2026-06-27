<?php

namespace App\Modules\Sdk\Development\Data;

use App\Modules\Sdk\Development\Enums\BusinessModuleStatus;
use App\Modules\Sdk\Development\Enums\BusinessModuleType;

readonly class BusinessModuleReference implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $moduleKey,
        public string $name,
        public string $status,
        public string $type,
        public string $version,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? ''),
            moduleKey: (string) ($data['module_key'] ?? $data['key'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            status: (string) ($data['status'] ?? BusinessModuleStatus::Draft->value),
            type: (string) ($data['type'] ?? BusinessModuleType::Business->value),
            version: (string) ($data['version'] ?? '0.1.0'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'module_key' => $this->moduleKey,
            'name' => $this->name,
            'status' => $this->status,
            'type' => $this->type,
            'version' => $this->version,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
