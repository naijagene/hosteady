<?php

namespace App\Modules\Sdk\Entity\Data;

use App\Modules\Sdk\Entity\Enums\EntityOwnershipScope;
use App\Modules\Sdk\Entity\Enums\EntityStatus;
use App\Modules\Sdk\Entity\Enums\EntityVisibility;

readonly class EntityDefinition implements \JsonSerializable
{
    /**
     * @param  list<EntityFieldDefinition>  $fields
     * @param  list<EntityRelationshipDefinition>  $relationships
     * @param  list<EntityValidationRule>  $validationRules
     */
    public function __construct(
        public string $moduleKey,
        public string $entityKey,
        public string $name,
        public ?string $publicId = null,
        public ?string $description = null,
        public ?string $icon = null,
        public string $status = EntityStatus::Registered->value,
        public string $visibility = EntityVisibility::Organization->value,
        public string $ownershipScope = EntityOwnershipScope::Organization->value,
        public ?string $tableName = null,
        public ?string $className = null,
        public array $capabilities = [],
        public array $fields = [],
        public array $relationships = [],
        public array $validationRules = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $fields = [];
        foreach (is_array($data['fields'] ?? null) ? $data['fields'] : [] as $field) {
            if (is_array($field)) {
                $fields[] = EntityFieldDefinition::fromArray($field);
            }
        }

        $relationships = [];
        foreach (is_array($data['relationships'] ?? null) ? $data['relationships'] : [] as $relationship) {
            if (is_array($relationship)) {
                $relationships[] = EntityRelationshipDefinition::fromArray($relationship);
            }
        }

        $validationRules = [];
        foreach (is_array($data['validation_rules'] ?? null) ? $data['validation_rules'] : [] as $rule) {
            if (is_array($rule)) {
                $validationRules[] = EntityValidationRule::fromArray($rule);
            }
        }

        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            entityKey: (string) ($data['entity_key'] ?? $data['key'] ?? ''),
            name: (string) ($data['name'] ?? $data['label'] ?? ''),
            publicId: isset($data['public_id']) ? (string) $data['public_id'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            icon: isset($data['icon']) ? (string) $data['icon'] : null,
            status: (string) ($data['status'] ?? EntityStatus::Registered->value),
            visibility: (string) ($data['visibility'] ?? EntityVisibility::Organization->value),
            ownershipScope: (string) ($data['ownership_scope'] ?? EntityOwnershipScope::Organization->value),
            tableName: isset($data['table_name']) ? (string) $data['table_name'] : null,
            className: isset($data['class_name']) ? (string) $data['class_name'] : null,
            capabilities: is_array($data['capabilities'] ?? null) ? $data['capabilities'] : [],
            fields: $fields,
            relationships: $relationships,
            validationRules: $validationRules,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'ownership_scope' => $this->ownershipScope,
            'table_name' => $this->tableName,
            'class_name' => $this->className,
            'capabilities' => $this->capabilities,
            'fields' => array_map(fn (EntityFieldDefinition $f) => $f->toArray(), $this->fields),
            'relationships' => array_map(fn (EntityRelationshipDefinition $r) => $r->toArray(), $this->relationships),
            'validation_rules' => array_map(fn (EntityValidationRule $r) => $r->toArray(), $this->validationRules),
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
