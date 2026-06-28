<?php

namespace App\Services\DataRepository;

use App\Models\EnterpriseEntityRecord;
use App\Models\EnterpriseEntityRecordVersion;
use App\Modules\Sdk\DataRepository\Data\EntityRecord;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class EnterpriseEntityRecordVersionService
{
    public function snapshot(EntityRecord $record, string $action): EnterpriseEntityRecordVersion
    {
        $model = EnterpriseEntityRecord::query()->where('public_id', $record->publicId)->first();
        $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

        return EnterpriseEntityRecordVersion::query()->create([
            'id' => (string) Str::uuid7(),
            'enterprise_entity_record_id' => $model?->id,
            'organization_id' => $record->organizationId,
            'workspace_id' => $record->workspaceId,
            'module_key' => $record->moduleKey,
            'entity_key' => $record->entityKey,
            'record_public_id' => $record->publicId,
            'version_number' => $record->version,
            'record_data' => $record->recordData->toArray(),
            'metadata' => array_merge($record->metadata, ['action' => $action]),
            'created_by_user_id' => $context?->user->id,
            'created_by_membership_id' => $context?->membership->id,
            'created_at' => now(),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
    ): array {
        $query = EnterpriseEntityRecordVersion::query()
            ->where('organization_id', $organizationId)
            ->where('module_key', $moduleKey)
            ->where('entity_key', $entityKey)
            ->where('record_public_id', $recordPublicId);

        if ($workspaceId !== null) {
            $query->where(function ($q) use ($workspaceId) {
                $q->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        return $query->orderByDesc('version_number')
            ->get()
            ->map(fn (EnterpriseEntityRecordVersion $model) => EnterpriseEntityRecordMapper::toVersionReference($model))
            ->all();
    }
}
