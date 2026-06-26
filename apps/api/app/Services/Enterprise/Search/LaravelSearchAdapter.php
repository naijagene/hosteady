<?php

namespace App\Services\Enterprise\Search;

use App\Enums\SearchVisibility;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\PlatformSavedSearch;
use App\Models\PlatformSearchIndex;
use App\Models\SearchActivityLog;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Contracts\IndexPort;
use App\Modules\Sdk\Enterprise\Contracts\SearchPort;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\SavedSearchReference;
use App\Modules\Sdk\Enterprise\Data\SearchIndexReference;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;
use App\Modules\Sdk\Enterprise\Data\SearchRequest;
use App\Modules\Sdk\Enterprise\Data\SearchResult;
use App\Services\Enterprise\Audit\EnterpriseSearchAuditRecorder;
use App\Support\Tenant\TenantContext;

class LaravelSearchAdapter implements SearchPort, IndexPort
{
    public function __construct(
        private readonly SearchModuleRegistry $moduleRegistry,
        private readonly EnterpriseSearchAuditRecorder $auditRecorder,
    ) {
    }

    public function search(SearchRequest $request): SearchResult
    {
        $organizationId = $this->organizationId($request->scope);
        $workspaceId = $this->workspaceId($request->scope, $organizationId);
        $queryText = trim((string) ($request->query ?? ''));

        $builder = PlatformSearchIndex::query()
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at');

        if ($workspaceId !== null) {
            $builder->where(function ($nested) use ($workspaceId) {
                $nested->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        if ($request->moduleKey !== null) {
            $builder->where('module_key', $request->moduleKey);
        }

        if ($request->entityType !== null) {
            $builder->where('entity_type', $request->entityType);
        }

        if ($queryText !== '') {
            $like = '%'.$queryText.'%';
            $builder->where(function ($nested) use ($like) {
                $nested->where('display_name', 'like', $like)
                    ->orWhere('keywords', 'like', $like);
            });
        }

        $builder->whereIn('visibility', [
            SearchVisibility::Organization->value,
            SearchVisibility::Workspace->value,
        ]);

        $items = $builder
            ->orderBy('display_name')
            ->limit(min($request->limit, (int) config('heos.enterprise.search.max_results', 50)))
            ->get()
            ->map(fn (PlatformSearchIndex $index) => $this->toIndexReference($index))
            ->all();

        if ($request->membershipPublicId !== null) {
            $membershipId = OrganizationMembership::query()
                ->where('public_id', $request->membershipPublicId)
                ->where('organization_id', $organizationId)
                ->value('id');

            if ($membershipId !== null) {
                SearchActivityLog::query()->create([
                    'organization_id' => $organizationId,
                    'workspace_id' => $workspaceId,
                    'membership_id' => $membershipId,
                    'query' => $queryText !== '' ? $queryText : null,
                    'result_count' => count($items),
                    'filters' => $request->filters,
                    'module_key' => $request->moduleKey,
                ]);
            }
        }

        return new SearchResult(
            query: $queryText,
            total: count($items),
            items: $items,
        );
    }

    /**
     * @return list<string>
     */
    public function suggestions(SearchRequest $request, int $limit = 10): array
    {
        $result = $this->search(new SearchRequest(
            scope: $request->scope,
            query: $request->query,
            moduleKey: $request->moduleKey,
            entityType: $request->entityType,
            limit: $limit,
        ));

        return array_values(array_unique(array_map(
            fn (SearchIndexReference $item) => $item->displayName,
            $result->items,
        )));
    }

    /**
     * @return list<array{query: ?string, module_key: ?string, result_count: int, created_at: string}>
     */
    public function recent(EnterpriseScope $scope, string $membershipPublicId, int $limit = 10): array
    {
        $organizationId = $this->organizationId($scope);
        $membershipId = OrganizationMembership::query()
            ->where('public_id', $membershipPublicId)
            ->where('organization_id', $organizationId)
            ->value('id');

        if ($membershipId === null) {
            return [];
        }

        return SearchActivityLog::query()
            ->where('organization_id', $organizationId)
            ->where('membership_id', $membershipId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (SearchActivityLog $log) => [
                'query' => $log->query,
                'module_key' => $log->module_key,
                'result_count' => $log->result_count,
                'created_at' => $log->created_at?->toIso8601String(),
            ])
            ->all();
    }

    public function saveSearch(
        EnterpriseScope $scope,
        string $membershipPublicId,
        string $name,
        ?string $query,
        ?array $filters = null,
        ?string $moduleKey = null,
    ): SavedSearchReference {
        $organizationId = $this->organizationId($scope);
        $workspaceId = $this->workspaceId($scope, $organizationId);
        $membership = OrganizationMembership::query()
            ->where('public_id', $membershipPublicId)
            ->where('organization_id', $organizationId)
            ->firstOrFail();

        $saved = PlatformSavedSearch::query()->create([
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'membership_id' => $membership->id,
            'name' => $name,
            'query' => $query,
            'filters' => $filters,
            'module_key' => $moduleKey,
        ]);

        $this->auditRecorder->recordSaved($saved);

        return $this->toSavedReference($saved);
    }

    /**
     * @return list<SavedSearchReference>
     */
    public function listSavedSearches(EnterpriseScope $scope, string $membershipPublicId): array
    {
        $organizationId = $this->organizationId($scope);
        $membershipId = OrganizationMembership::query()
            ->where('public_id', $membershipPublicId)
            ->where('organization_id', $organizationId)
            ->value('id');

        if ($membershipId === null) {
            return [];
        }

        return PlatformSavedSearch::query()
            ->where('organization_id', $organizationId)
            ->where('membership_id', $membershipId)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PlatformSavedSearch $saved) => $this->toSavedReference($saved))
            ->all();
    }

    public function deleteSavedSearch(EnterpriseScope $scope, string $membershipPublicId, string $savedSearchPublicId): void
    {
        $organizationId = $this->organizationId($scope);
        $membershipId = OrganizationMembership::query()
            ->where('public_id', $membershipPublicId)
            ->where('organization_id', $organizationId)
            ->value('id');

        $saved = PlatformSavedSearch::query()
            ->where('public_id', $savedSearchPublicId)
            ->where('organization_id', $organizationId)
            ->where('membership_id', $membershipId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $saved->delete();

        $this->auditRecorder->recordDeleted($saved);
    }

    public function upsert(SearchIndexUpsertRequest $request): SearchIndexReference
    {
        $organizationId = $this->organizationId($request->scope);
        $workspaceId = $this->workspaceId($request->scope, $organizationId);
        $visibility = SearchVisibility::tryFrom($request->visibility) ?? SearchVisibility::Organization;

        $index = PlatformSearchIndex::query()->updateOrCreate(
            [
                'organization_id' => $organizationId,
                'module_key' => $request->scope->moduleKey,
                'entity_type' => $request->entityType,
                'entity_public_id' => $request->entityPublicId,
            ],
            [
                'workspace_id' => $workspaceId,
                'entity_reference' => $request->entityReference?->toArray(),
                'display_name' => $request->displayName,
                'keywords' => $request->keywords,
                'metadata' => $request->metadata,
                'visibility' => $visibility,
                'search_vector' => $request->searchVector,
            ],
        );

        $this->auditRecorder->recordIndexUpdated($index);

        return $this->toIndexReference($index);
    }

    public function remove(EnterpriseScope $scope, string $entityType, string $entityPublicId, ?string $moduleKey = null): void
    {
        PlatformSearchIndex::query()
            ->where('organization_id', $this->organizationId($scope))
            ->where('entity_type', $entityType)
            ->where('entity_public_id', $entityPublicId)
            ->when($moduleKey !== null, fn ($query) => $query->where('module_key', $moduleKey))
            ->delete();
    }

    public function registerModule(string $moduleKey, array $entityTypes): void
    {
        $this->moduleRegistry->register($moduleKey, $entityTypes);
    }

    /**
     * @return list<string>
     */
    public function supportedModules(): array
    {
        return $this->moduleRegistry->moduleKeys();
    }

    private function organizationId(EnterpriseScope $scope): string
    {
        return (string) Organization::query()
            ->where('public_id', $scope->organizationPublicId)
            ->value('id');
    }

    private function workspaceId(EnterpriseScope $scope, string $organizationId): ?string
    {
        if ($scope->workspacePublicId === null) {
            return null;
        }

        return Workspace::query()
            ->where('public_id', $scope->workspacePublicId)
            ->where('organization_id', $organizationId)
            ->value('id');
    }

    private function toIndexReference(PlatformSearchIndex $index): SearchIndexReference
    {
        $entityReference = null;

        if (is_array($index->entity_reference) && isset($index->entity_reference['type'])) {
            $entityReference = EntityReference::fromArray($index->entity_reference);
        }

        return new SearchIndexReference(
            publicId: $index->public_id,
            displayName: $index->display_name,
            entityType: $index->entity_type,
            entityPublicId: $index->entity_public_id,
            moduleKey: $index->module_key,
            entityReference: $entityReference,
            visibility: $index->visibility->value,
            metadata: $index->metadata ?? [],
            keywords: $index->keywords,
        );
    }

    private function toSavedReference(PlatformSavedSearch $saved): SavedSearchReference
    {
        return new SavedSearchReference(
            publicId: $saved->public_id,
            name: $saved->name,
            query: $saved->query,
            filters: $saved->filters,
            moduleKey: $saved->module_key,
            createdAt: $saved->created_at?->toIso8601String(),
        );
    }
}
