<?php

namespace App\Modules\Sdk\Development\Data;

readonly class BusinessModuleInstallRequest implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $modulePublicId,
        public array $settings = [],
        public array $metadata = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            modulePublicId: (string) ($data['module_public_id'] ?? ''),
            settings: is_array($data['settings'] ?? null) ? $data['settings'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'module_public_id' => $this->modulePublicId,
            'settings' => $this->settings,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
