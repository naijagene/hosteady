<?php

namespace App\Modules\Sdk\DataRepository\Data;

readonly class EntityRecordUpdateRequest implements \JsonSerializable
{
    public function __construct(
        public string $moduleKey,
        public string $entityKey,
        public string $recordPublicId,
        public array $values = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            entityKey: (string) ($data['entity_key'] ?? ''),
            recordPublicId: (string) ($data['record_public_id'] ?? ''),
            values: is_array($data['values'] ?? null) ? $data['values'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'record_public_id' => $this->recordPublicId,
            'values' => $this->values,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
