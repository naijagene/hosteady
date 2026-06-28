<?php

namespace App\Modules\Sdk\DataRepository\Data;

readonly class EntityRecordCreateRequest implements \JsonSerializable
{
    public function __construct(
        public string $moduleKey,
        public string $entityKey,
        public array $values = [],
        public string $visibility = 'organization',
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            entityKey: (string) ($data['entity_key'] ?? ''),
            values: is_array($data['values'] ?? null) ? $data['values'] : [],
            visibility: (string) ($data['visibility'] ?? 'organization'),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'values' => $this->values,
            'visibility' => $this->visibility,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
