<?php

namespace App\Modules\Sdk\Entity\Data;

readonly class EntityMutationRequest implements \JsonSerializable
{
    public function __construct(
        public string $moduleKey,
        public string $entityKey,
        public string $operation,
        public ?string $entityPublicId = null,
        public array $attributes = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            entityKey: (string) ($data['entity_key'] ?? ''),
            operation: (string) ($data['operation'] ?? 'create'),
            entityPublicId: isset($data['entity_public_id']) ? (string) $data['entity_public_id'] : null,
            attributes: is_array($data['attributes'] ?? null) ? $data['attributes'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'operation' => $this->operation,
            'entity_public_id' => $this->entityPublicId,
            'attributes' => $this->attributes,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
