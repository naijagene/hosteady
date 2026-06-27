<?php

namespace App\Services\Entity;

use App\Models\EntityTag;
use App\Models\EntityTaggable;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Entity\Exceptions\EntityNotFoundException;

class EnterpriseEntityTagService
{
    public function __construct(
        private readonly EnterpriseEntityAuditRecorder $auditRecorder,
        private readonly EnterpriseEntitySearchIndexer $searchIndexer,
        private readonly EnterpriseEntityLifecycleService $lifecycleService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function createTag(
        EnterpriseScope $scope,
        string $organizationId,
        ?string $workspaceId,
        string $tagKey,
        string $name,
        ?string $color = null,
        array $metadata = [],
    ): array {
        $model = EntityTag::query()->create([
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'tag_key' => $tagKey,
            'name' => $name,
            'color' => $color,
            'metadata' => $metadata,
        ]);

        $this->auditRecorder->recordTagCreated($model);
        $this->searchIndexer->indexTagBestEffort($model, $scope);

        return EnterpriseEntityMapper::toTagReference($model);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listTags(string $organizationId, ?string $workspaceId): array
    {
        $query = EntityTag::query()
            ->where('organization_id', $organizationId)
            ->orderBy('name');

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        }

        return $query->get()
            ->map(fn (EntityTag $model) => EnterpriseEntityMapper::toTagReference($model))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listTagsForEntity(
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
    ): array {
        $query = EntityTaggable::query()
            ->with('entityTag')
            ->where('organization_id', $organizationId)
            ->where('module_key', $moduleKey)
            ->where('entity_key', $entityKey)
            ->where('entity_public_id', $entityPublicId);

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        }

        return $query->get()
            ->map(function (EntityTaggable $taggable) {
                $tag = $taggable->entityTag;

                return $tag === null ? null : EnterpriseEntityMapper::toTagReference($tag);
            })
            ->filter()
            ->values()
            ->all();
    }

    public function attachTag(
        EnterpriseScope $scope,
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
        string $tagPublicId,
    ): void {
        $tag = $this->findTag($organizationId, $workspaceId, $tagPublicId);

        EntityTaggable::query()->firstOrCreate([
            'entity_tag_id' => $tag->id,
            'module_key' => $moduleKey,
            'entity_key' => $entityKey,
            'entity_public_id' => $entityPublicId,
        ], [
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
        ]);

        $this->auditRecorder->recordTagAttached($moduleKey, $entityKey, $entityPublicId, $tag);
        $this->lifecycleService->dispatchTagged($scope, $moduleKey, $entityKey, $entityPublicId, $tagPublicId);
    }

    public function detachTag(
        EnterpriseScope $scope,
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
        string $tagPublicId,
    ): void {
        $tag = $this->findTag($organizationId, $workspaceId, $tagPublicId);

        EntityTaggable::query()
            ->where('entity_tag_id', $tag->id)
            ->where('organization_id', $organizationId)
            ->where('module_key', $moduleKey)
            ->where('entity_key', $entityKey)
            ->where('entity_public_id', $entityPublicId)
            ->when($workspaceId !== null, fn ($query) => $query->where('workspace_id', $workspaceId))
            ->delete();

        $this->auditRecorder->recordTagDetached($moduleKey, $entityKey, $entityPublicId, $tag);
        $this->lifecycleService->dispatchUntagged($scope, $moduleKey, $entityKey, $entityPublicId, $tagPublicId);
    }

    private function findTag(string $organizationId, ?string $workspaceId, string $tagPublicId): EntityTag
    {
        $query = EntityTag::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $tagPublicId);

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        }

        $tag = $query->first();

        if ($tag === null) {
            throw new EntityNotFoundException(sprintf('Entity tag [%s] was not found.', $tagPublicId));
        }

        return $tag;
    }
}
