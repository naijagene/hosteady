<?php

namespace App\Services\DataRepository;

use App\Models\EnterpriseEntityRecord;
use App\Models\EnterpriseEntityRecordActivity;
use App\Models\EnterpriseEntityRecordLink;
use App\Models\EnterpriseEntityRecordVersion;
use App\Modules\Sdk\DataRepository\Data\EntityRecord;
use App\Modules\Sdk\DataRepository\Data\EntityRecordData;

class EnterpriseEntityRecordMapper
{
    public static function toRecord(EnterpriseEntityRecord $model): EntityRecord
    {
        $recordData = is_array($model->record_data) ? $model->record_data : [];

        return new EntityRecord(
            moduleKey: $model->module_key,
            entityKey: $model->entity_key,
            publicId: $model->public_id,
            organizationId: $model->organization_id,
            workspaceId: $model->workspace_id,
            recordData: EntityRecordData::fromArray($recordData),
            status: (string) $model->status,
            visibility: (string) $model->visibility,
            version: (int) $model->version,
            searchText: $model->search_text,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
            deletedAt: $model->deleted_at?->toIso8601String(),
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toVersionReference(EnterpriseEntityRecordVersion $model): array
    {
        return [
            'public_id' => $model->public_id,
            'record_public_id' => $model->record_public_id,
            'version_number' => $model->version_number,
            'record_data' => is_array($model->record_data) ? $model->record_data : [],
            'metadata' => is_array($model->metadata) ? $model->metadata : [],
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toLinkReference(EnterpriseEntityRecordLink $model): array
    {
        return [
            'public_id' => $model->public_id,
            'source_module_key' => $model->source_module_key,
            'source_entity_key' => $model->source_entity_key,
            'source_record_public_id' => $model->source_record_public_id,
            'target_module_key' => $model->target_module_key,
            'target_entity_key' => $model->target_entity_key,
            'target_record_public_id' => $model->target_record_public_id,
            'relationship_key' => $model->relationship_key,
            'metadata' => is_array($model->metadata) ? $model->metadata : [],
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toActivityReference(EnterpriseEntityRecordActivity $model): array
    {
        return [
            'public_id' => $model->public_id,
            'module_key' => $model->module_key,
            'entity_key' => $model->entity_key,
            'record_public_id' => $model->record_public_id,
            'action' => $model->action,
            'before_state' => is_array($model->before_state) ? $model->before_state : [],
            'after_state' => is_array($model->after_state) ? $model->after_state : [],
            'metadata' => is_array($model->metadata) ? $model->metadata : [],
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function buildSearchText(array $values): ?string
    {
        $parts = [];
        foreach ($values as $value) {
            if (is_scalar($value) && $value !== '') {
                $parts[] = (string) $value;
            }
        }

        if ($parts === []) {
            return null;
        }

        return mb_strtolower(implode(' ', $parts));
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function entityBindingEnabled(array $metadata): bool
    {
        $binding = $metadata['entity_binding'] ?? null;

        return is_array($binding) && ($binding['enabled'] ?? false) === true;
    }
}
