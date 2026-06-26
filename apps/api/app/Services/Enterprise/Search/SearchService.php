<?php

namespace App\Services\Enterprise\Search;

use App\Modules\Sdk\Enterprise\Contracts\IndexPort;
use App\Modules\Sdk\Enterprise\Contracts\SearchPort;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\SavedSearchReference;
use App\Modules\Sdk\Enterprise\Data\SearchIndexReference;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;
use App\Modules\Sdk\Enterprise\Data\SearchRequest;
use App\Modules\Sdk\Enterprise\Data\SearchResult;
use App\Services\Enterprise\Audit\EnterpriseSearchAuditRecorder;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;

class SearchService
{
    public function __construct(
        private readonly SearchPort $searchPort,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
        private readonly EnterpriseSearchAuditRecorder $auditRecorder,
    ) {
    }

    public function search(TenantContext $context, SearchRequest $request): SearchResult
    {
        $this->runtimeBridge->requireCapability($context, 'search');
        $this->assertReadPermission($context);

        $result = $this->searchPort->search(new SearchRequest(
            scope: $this->scopeFromContext($context, $request->scope->moduleKey),
            query: $request->query,
            moduleKey: $request->moduleKey ?? $request->scope->moduleKey,
            entityType: $request->entityType,
            membershipPublicId: $context->membershipPublicId,
            filters: $request->filters,
            limit: $request->limit,
        ));

        $this->auditRecorder->recordExecuted(
            $context,
            $request->query,
            $result->total,
            $request->moduleKey ?? $request->scope->moduleKey,
        );

        return $result;
    }

    /**
     * @return list<string>
     */
    public function suggestions(TenantContext $context, ?string $query, ?string $moduleKey = null, int $limit = 10): array
    {
        $this->runtimeBridge->requireCapability($context, 'search');
        $this->assertReadPermission($context);

        return $this->searchPort->suggestions(new SearchRequest(
            scope: $this->scopeFromContext($context, $moduleKey),
            query: $query,
            moduleKey: $moduleKey,
            limit: $limit,
        ), $limit);
    }

    /**
     * @return list<array{query: ?string, module_key: ?string, result_count: int, created_at: string}>
     */
    public function recent(TenantContext $context, int $limit = 10): array
    {
        $this->runtimeBridge->requireCapability($context, 'search');
        $this->assertReadPermission($context);

        return $this->searchPort->recent($this->scopeFromContext($context), $context->membershipPublicId, $limit);
    }

    public function saveSearch(
        TenantContext $context,
        string $name,
        ?string $query = null,
        ?array $filters = null,
        ?string $moduleKey = null,
    ): SavedSearchReference {
        $this->runtimeBridge->requireCapability($context, 'search');
        $this->assertReadPermission($context);

        return $this->searchPort->saveSearch(
            $this->scopeFromContext($context, $moduleKey),
            $context->membershipPublicId,
            $name,
            $query,
            $filters,
            $moduleKey,
        );
    }

    /**
     * @return list<SavedSearchReference>
     */
    public function listSavedSearches(TenantContext $context): array
    {
        $this->runtimeBridge->requireCapability($context, 'search');
        $this->assertReadPermission($context);

        return $this->searchPort->listSavedSearches($this->scopeFromContext($context), $context->membershipPublicId);
    }

    public function deleteSavedSearch(TenantContext $context, string $savedSearchPublicId): void
    {
        $this->runtimeBridge->requireCapability($context, 'search');
        $this->assertManagePermission($context);

        $this->searchPort->deleteSavedSearch(
            $this->scopeFromContext($context),
            $context->membershipPublicId,
            $savedSearchPublicId,
        );
    }

    private function scopeFromContext(TenantContext $context, ?string $moduleKey = null): EnterpriseScope
    {
        return new EnterpriseScope(
            organizationPublicId: $context->organizationPublicId,
            workspacePublicId: $context->workspacePublicId,
            moduleKey: $moduleKey,
        );
    }

    private function assertReadPermission(TenantContext $context): void
    {
        if (! $this->allows($context, 'search.read')) {
            abort(403, 'You are not allowed to search.');
        }
    }

    private function assertManagePermission(TenantContext $context): void
    {
        if (! $this->allows($context, 'search.manage') && ! $this->allows($context, 'search.read')) {
            abort(403, 'You are not allowed to manage saved searches.');
        }
    }

    private function allows(TenantContext $context, string $permission): bool
    {
        return app(\App\Services\Authorization\TenantAuthorizationService::class)
            ->allows($context, $permission);
    }
}
