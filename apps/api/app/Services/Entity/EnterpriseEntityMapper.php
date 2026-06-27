<?php

namespace App\Services\Entity;

use App\Models\EntityActivityLog;
use App\Models\EntityComment;
use App\Models\EntityDefinition as EntityDefinitionModel;
use App\Models\EntityRelationship;
use App\Models\EntityTag;
use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Modules\Sdk\Entity\Data\EntityFieldDefinition;
use App\Modules\Sdk\Entity\Data\EntityRelationshipDefinition;
use App\Modules\Sdk\Entity\Data\EntityValidationRule;

class EnterpriseEntityMapper
{
    public static function toDefinition(EntityDefinitionModel $model): EntityDefinition
    {
        $fields = [];
        foreach (is_array($model->fields) ? $model->fields : [] as $field) {
            if (is_array($field)) {
                $fields[] = EntityFieldDefinition::fromArray($field);
            }
        }

        $relationships = [];
        foreach (is_array($model->relationships) ? $model->relationships : [] as $relationship) {
            if (is_array($relationship)) {
                $relationships[] = EntityRelationshipDefinition::fromArray($relationship);
            }
        }

        $validationRules = [];
        foreach (is_array($model->validation_rules) ? $model->validation_rules : [] as $rule) {
            if (is_array($rule)) {
                $validationRules[] = EntityValidationRule::fromArray($rule);
            }
        }

        return new EntityDefinition(
            moduleKey: $model->module_key,
            entityKey: $model->entity_key,
            name: $model->name,
            publicId: $model->public_id,
            description: $model->description,
            icon: $model->icon,
            status: (string) $model->status,
            visibility: (string) $model->visibility,
            ownershipScope: (string) $model->ownership_scope,
            tableName: $model->table_name,
            className: $model->class_name,
            capabilities: is_array($model->capabilities) ? $model->capabilities : [],
            fields: $fields,
            relationships: $relationships,
            validationRules: $validationRules,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function applyDefinition(EntityDefinitionModel $model, EntityDefinition $definition): void
    {
        $model->fill([
            'module_key' => $definition->moduleKey,
            'entity_key' => $definition->entityKey,
            'name' => $definition->name,
            'description' => $definition->description,
            'icon' => $definition->icon,
            'status' => $definition->status,
            'visibility' => $definition->visibility,
            'ownership_scope' => $definition->ownershipScope,
            'table_name' => $definition->tableName,
            'class_name' => $definition->className,
            'capabilities' => $definition->capabilities,
            'fields' => array_map(fn (EntityFieldDefinition $f) => $f->toArray(), $definition->fields),
            'relationships' => array_map(fn (EntityRelationshipDefinition $r) => $r->toArray(), $definition->relationships),
            'validation_rules' => array_map(fn (EntityValidationRule $r) => $r->toArray(), $definition->validationRules),
            'metadata' => $definition->metadata,
            'registered_at' => $model->registered_at ?? now(),
        ]);
    }

    /**
     * @return array{public_id: string}
     */
    public static function toReference(EntityDefinitionModel $model): array
    {
        return [
            'public_id' => $model->public_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toRelationshipReference(EntityRelationship $model): array
    {
        return [
            'public_id' => $model->public_id,
            'relationship_key' => $model->relationship_key,
            'relationship_type' => $model->relationship_type,
            'source_module_key' => $model->source_module_key,
            'source_entity_key' => $model->source_entity_key,
            'target_module_key' => $model->target_module_key,
            'target_entity_key' => $model->target_entity_key,
            'label' => $model->label,
            'metadata' => is_array($model->metadata) ? $model->metadata : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toCommentReference(EntityComment $model): array
    {
        return [
            'public_id' => $model->public_id,
            'module_key' => $model->module_key,
            'entity_key' => $model->entity_key,
            'entity_public_id' => $model->entity_public_id,
            'comment_body' => $model->comment_body,
            'metadata' => is_array($model->metadata) ? $model->metadata : [],
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toTagReference(EntityTag $model): array
    {
        return [
            'public_id' => $model->public_id,
            'tag_key' => $model->tag_key,
            'name' => $model->name,
            'color' => $model->color,
            'metadata' => is_array($model->metadata) ? $model->metadata : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toActivityReference(EntityActivityLog $model): array
    {
        return [
            'public_id' => $model->public_id,
            'module_key' => $model->module_key,
            'entity_key' => $model->entity_key,
            'entity_public_id' => $model->entity_public_id,
            'action' => $model->action,
            'before_state' => is_array($model->before_state) ? $model->before_state : [],
            'after_state' => is_array($model->after_state) ? $model->after_state : [],
            'metadata' => is_array($model->metadata) ? $model->metadata : [],
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }
}
