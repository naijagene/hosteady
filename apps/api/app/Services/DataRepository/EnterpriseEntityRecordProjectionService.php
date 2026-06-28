<?php

namespace App\Services\DataRepository;

use App\Modules\Sdk\DataRepository\Contracts\EntityRecordProjectionProvider;
use App\Modules\Sdk\DataRepository\Data\EntityRecord;
use App\Modules\Sdk\DataRepository\Data\EntityRecordProjection;

class EnterpriseEntityRecordProjectionService implements EntityRecordProjectionProvider
{
    /**
     * @return array<string, mixed>
     */
    public function project(EntityRecord $record, ?EntityRecordProjection $projection = null): array
    {
        $values = $record->recordData->values;

        if ($projection !== null && $projection->fields !== []) {
            $values = array_intersect_key($values, array_flip($projection->fields));
        }

        $payload = [
            'public_id' => $record->publicId,
            'module_key' => $record->moduleKey,
            'entity_key' => $record->entityKey,
            'status' => $record->status,
            'visibility' => $record->visibility,
            'version' => $record->version,
            'values' => $values,
            'created_at' => $record->createdAt,
            'updated_at' => $record->updatedAt,
        ];

        if ($projection?->includeMetadata) {
            $payload['metadata'] = $record->metadata;
            $payload['record_metadata'] = $record->recordData->metadata;
        }

        return $payload;
    }
}
