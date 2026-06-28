<?php

namespace App\Services\DataRepository;

use App\Models\EnterpriseEntityRecordLink;
use App\Modules\Sdk\DataRepository\Exceptions\EntityRecordNotFoundException;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class EnterpriseEntityRecordRelationshipService
{
    public function __construct(
        private readonly EnterpriseEntityRecordRepositoryService $repositoryService,
        private readonly EnterpriseEntityRecordLifecycleService $lifecycleService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function link(
        string $organizationId,
        ?string $workspaceId,
        string $sourceModuleKey,
        string $sourceEntityKey,
        string $sourceRecordPublicId,
        string $targetModuleKey,
        string $targetEntityKey,
        string $targetRecordPublicId,
        string $relationshipKey,
        array $metadata = [],
    ): array {
        $this->assertRecordExists($organizationId, $workspaceId, $sourceModuleKey, $sourceEntityKey, $sourceRecordPublicId);
        $this->assertRecordExists($organizationId, $workspaceId, $targetModuleKey, $targetEntityKey, $targetRecordPublicId);

        $model = EnterpriseEntityRecordLink::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'source_module_key' => $sourceModuleKey,
            'source_entity_key' => $sourceEntityKey,
            'source_record_public_id' => $sourceRecordPublicId,
            'target_module_key' => $targetModuleKey,
            'target_entity_key' => $targetEntityKey,
            'target_record_public_id' => $targetRecordPublicId,
            'relationship_key' => $relationshipKey,
            'metadata' => $metadata,
        ]);

        $reference = EnterpriseEntityRecordMapper::toLinkReference($model);

        if (app()->bound(TenantContext::class)) {
            $this->lifecycleService->dispatchLinked(app(TenantContext::class), $reference);
        }

        return $reference;
    }

    public function unlink(string $organizationId, ?string $workspaceId, string $linkPublicId): void
    {
        $query = EnterpriseEntityRecordLink::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $linkPublicId);

        if ($workspaceId !== null) {
            $query->where(function ($q) use ($workspaceId) {
                $q->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        $model = $query->first();

        if ($model === null) {
            throw new EntityRecordNotFoundException(sprintf('Entity record link [%s] was not found.', $linkPublicId));
        }

        $reference = EnterpriseEntityRecordMapper::toLinkReference($model);
        $model->delete();

        if (app()->bound(TenantContext::class)) {
            $this->lifecycleService->dispatchUnlinked(app(TenantContext::class), $reference);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listLinks(
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
    ): array {
        $query = EnterpriseEntityRecordLink::query()
            ->where('organization_id', $organizationId)
            ->where('source_module_key', $moduleKey)
            ->where('source_entity_key', $entityKey)
            ->where('source_record_public_id', $recordPublicId);

        if ($workspaceId !== null) {
            $query->where(function ($q) use ($workspaceId) {
                $q->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        return $query->orderByDesc('created_at')
            ->get()
            ->map(fn (EnterpriseEntityRecordLink $model) => EnterpriseEntityRecordMapper::toLinkReference($model))
            ->all();
    }

    private function assertRecordExists(
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
    ): void {
        if ($this->repositoryService->find($organizationId, $workspaceId, $moduleKey, $entityKey, $recordPublicId) === null) {
            throw new EntityRecordNotFoundException(sprintf('Entity record [%s] was not found.', $recordPublicId));
        }
    }
}
