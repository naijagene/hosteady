<?php

namespace App\Modules\Sdk\Entity\Data;

use App\Modules\Sdk\Entity\Enums\EntityRelationshipType;

readonly class EntityRelationshipDefinition implements \JsonSerializable
{
    public function __construct(
        public string $relationshipKey,
        public string $relationshipType,
        public string $sourceModuleKey,
        public string $sourceEntityKey,
        public ?string $targetModuleKey = null,
        public ?string $targetEntityKey = null,
        public ?string $label = null,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            relationshipKey: (string) ($data['relationship_key'] ?? $data['key'] ?? ''),
            relationshipType: (string) ($data['relationship_type'] ?? $data['type'] ?? EntityRelationshipType::References->value),
            sourceModuleKey: (string) ($data['source_module_key'] ?? $data['module_key'] ?? ''),
            sourceEntityKey: (string) ($data['source_entity_key'] ?? $data['entity_key'] ?? ''),
            targetModuleKey: isset($data['target_module_key']) ? (string) $data['target_module_key'] : null,
            targetEntityKey: isset($data['target_entity_key']) ? (string) $data['target_entity_key'] : null,
            label: isset($data['label']) ? (string) $data['label'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'relationship_key' => $this->relationshipKey,
            'relationship_type' => $this->relationshipType,
            'source_module_key' => $this->sourceModuleKey,
            'source_entity_key' => $this->sourceEntityKey,
            'target_module_key' => $this->targetModuleKey,
            'target_entity_key' => $this->targetEntityKey,
            'label' => $this->label,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
