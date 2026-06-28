<?php

namespace App\Modules\Sdk\DataRepository\Data;

readonly class EntityRecordReference implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $moduleKey,
        public string $entityKey,
        public ?string $status = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? ''),
            moduleKey: (string) ($data['module_key'] ?? ''),
            entityKey: (string) ($data['entity_key'] ?? ''),
            status: isset($data['status']) ? (string) $data['status'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'public_id' => $this->publicId,
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'status' => $this->status,
        ], fn ($value) => $value !== null);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
