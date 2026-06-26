<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class EntityReference
{
    public function __construct(
        public string $type,
        public string $publicId,
        public ?string $moduleKey = null,
        public ?string $label = null,
    ) {
    }

    /**
     * @return array{type: string, public_id: string, module_key: ?string, label: ?string}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'public_id' => $this->publicId,
            'module_key' => $this->moduleKey,
            'label' => $this->label,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            type: (string) $payload['type'],
            publicId: (string) $payload['public_id'],
            moduleKey: isset($payload['module_key']) ? (string) $payload['module_key'] : null,
            label: isset($payload['label']) ? (string) $payload['label'] : null,
        );
    }
}
