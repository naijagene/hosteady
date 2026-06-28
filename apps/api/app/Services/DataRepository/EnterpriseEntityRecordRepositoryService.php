<?php

namespace App\Services\DataRepository;

use App\Models\EnterpriseEntityRecord;
use App\Modules\Sdk\DataRepository\Contracts\EntityRecordRepository;
use App\Modules\Sdk\DataRepository\Data\EntityRecord;
use App\Modules\Sdk\DataRepository\Data\EntityRecordCreateRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordDeleteRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordRestoreRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordUpdateRequest;
use App\Modules\Sdk\DataRepository\Enums\EntityRecordStatus;
use App\Modules\Sdk\DataRepository\Exceptions\EntityRecordNotFoundException;
use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Services\Entity\EnterpriseEntityRegistryService;
use Illuminate\Support\Str;

class EnterpriseEntityRecordRepositoryService implements EntityRecordRepository
{
    public function __construct(
        private readonly EnterpriseEntityRegistryService $registryService,
    ) {
    }

    public function resolveDefinition(string $moduleKey, string $entityKey): EntityDefinition
    {
        $definition = $this->registryService->find($moduleKey, $entityKey);

        if ($definition === null) {
            throw new EntityRecordNotFoundException(sprintf(
                'Entity definition [%s.%s] was not found.',
                $moduleKey,
                $entityKey,
            ));
        }

        return $definition;
    }

    public function find(
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
        bool $withTrashed = false,
    ): ?EntityRecord {
        $query = EnterpriseEntityRecord::query()
            ->where('organization_id', $organizationId)
            ->where('module_key', $moduleKey)
            ->where('entity_key', $entityKey)
            ->where('public_id', $recordPublicId);

        if ($workspaceId !== null) {
            $query->where(function ($q) use ($workspaceId) {
                $q->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        if ($withTrashed) {
            $query->withTrashed();
        }

        $model = $query->first();

        return $model !== null ? EnterpriseEntityRecordMapper::toRecord($model) : null;
    }

    public function create(string $organizationId, ?string $workspaceId, EntityRecordCreateRequest $request): EntityRecord
    {
        $this->resolveDefinition($request->moduleKey, $request->entityKey);

        $model = EnterpriseEntityRecord::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'module_key' => $request->moduleKey,
            'entity_key' => $request->entityKey,
            'record_data' => ['values' => $request->values, 'metadata' => $request->metadata],
            'search_text' => EnterpriseEntityRecordMapper::buildSearchText($request->values),
            'status' => EntityRecordStatus::Active->value,
            'visibility' => $request->visibility,
            'version' => 1,
            'metadata' => $request->metadata,
        ]);

        return EnterpriseEntityRecordMapper::toRecord($model);
    }

    public function update(string $organizationId, ?string $workspaceId, EntityRecordUpdateRequest $request): EntityRecord
    {
        $model = $this->resolveModel($organizationId, $workspaceId, $request->moduleKey, $request->entityKey, $request->recordPublicId);
        $existingValues = is_array($model->record_data['values'] ?? null) ? $model->record_data['values'] : [];
        $mergedValues = array_merge($existingValues, $request->values);

        $model->fill([
            'record_data' => [
                'values' => $mergedValues,
                'metadata' => array_merge(
                    is_array($model->record_data['metadata'] ?? null) ? $model->record_data['metadata'] : [],
                    $request->metadata,
                ),
            ],
            'search_text' => EnterpriseEntityRecordMapper::buildSearchText($mergedValues),
            'version' => ((int) $model->version) + 1,
            'metadata' => array_merge(is_array($model->metadata) ? $model->metadata : [], $request->metadata),
        ]);
        $model->save();

        return EnterpriseEntityRecordMapper::toRecord($model->fresh());
    }

    public function delete(string $organizationId, ?string $workspaceId, EntityRecordDeleteRequest $request): EntityRecord
    {
        $model = $this->resolveModel($organizationId, $workspaceId, $request->moduleKey, $request->entityKey, $request->recordPublicId);
        $model->status = EntityRecordStatus::Deleted->value;
        $model->save();
        $model->delete();

        $deleted = EnterpriseEntityRecord::query()->withTrashed()->where('public_id', $model->public_id)->firstOrFail();

        return EnterpriseEntityRecordMapper::toRecord($deleted);
    }

    public function restore(string $organizationId, ?string $workspaceId, EntityRecordRestoreRequest $request): EntityRecord
    {
        $model = EnterpriseEntityRecord::query()
            ->withTrashed()
            ->where('organization_id', $organizationId)
            ->where('module_key', $request->moduleKey)
            ->where('entity_key', $request->entityKey)
            ->where('public_id', $request->recordPublicId)
            ->first();

        if ($model === null) {
            throw new EntityRecordNotFoundException(sprintf(
                'Entity record [%s] was not found.',
                $request->recordPublicId,
            ));
        }

        if ($workspaceId !== null && $model->workspace_id !== null && $model->workspace_id !== $workspaceId) {
            throw new EntityRecordNotFoundException(sprintf(
                'Entity record [%s] was not found.',
                $request->recordPublicId,
            ));
        }

        $model->restore();
        $model->status = EntityRecordStatus::Active->value;
        $model->save();

        return EnterpriseEntityRecordMapper::toRecord($model->fresh());
    }

    private function resolveModel(
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
    ): EnterpriseEntityRecord {
        $query = EnterpriseEntityRecord::query()
            ->where('organization_id', $organizationId)
            ->where('module_key', $moduleKey)
            ->where('entity_key', $entityKey)
            ->where('public_id', $recordPublicId);

        if ($workspaceId !== null) {
            $query->where(function ($q) use ($workspaceId) {
                $q->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        $model = $query->first();

        if ($model === null) {
            throw new EntityRecordNotFoundException(sprintf(
                'Entity record [%s] was not found.',
                $recordPublicId,
            ));
        }

        return $model;
    }
}
