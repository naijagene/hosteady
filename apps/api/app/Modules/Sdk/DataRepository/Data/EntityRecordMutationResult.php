<?php

namespace App\Modules\Sdk\DataRepository\Data;

readonly class EntityRecordMutationResult implements \JsonSerializable
{
    public function __construct(
        public string $moduleKey,
        public string $entityKey,
        public string $mutationType,
        public bool $success,
        public ?string $recordPublicId = null,
        public ?EntityRecord $record = null,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $record = null;
        if (is_array($data['record'] ?? null)) {
            $record = EntityRecord::fromArray($data['record']);
        }

        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            entityKey: (string) ($data['entity_key'] ?? ''),
            mutationType: (string) ($data['mutation_type'] ?? $data['mutationType'] ?? 'create'),
            success: (bool) ($data['success'] ?? false),
            recordPublicId: isset($data['record_public_id']) ? (string) $data['record_public_id'] : null,
            record: $record,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'mutation_type' => $this->mutationType,
            'success' => $this->success,
            'record_public_id' => $this->recordPublicId,
            'record' => $this->record?->toArray(),
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
