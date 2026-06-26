<?php

namespace App\Services\Enterprise\Search;

use App\Modules\Sdk\Enterprise\Contracts\IndexPort;
use App\Modules\Sdk\Enterprise\Data\SearchIndexReference;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;

class SearchIndexService
{
    public function __construct(
        private readonly IndexPort $indexPort,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
    ) {
    }

    public function upsert(TenantContext $context, SearchIndexUpsertRequest $request): SearchIndexReference
    {
        $this->runtimeBridge->requireCapability($context, 'indexing');

        return $this->indexPort->upsert(new SearchIndexUpsertRequest(
            scope: new EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
                moduleKey: $request->scope->moduleKey,
            ),
            entityType: $request->entityType,
            entityPublicId: $request->entityPublicId,
            displayName: $request->displayName,
            keywords: $request->keywords,
            metadata: $request->metadata,
            entityReference: $request->entityReference,
            visibility: $request->visibility,
            searchVector: $request->searchVector,
        ));
    }

    public function remove(TenantContext $context, string $entityType, string $entityPublicId, ?string $moduleKey = null): void
    {
        $this->runtimeBridge->requireCapability($context, 'indexing');

        $this->indexPort->remove(
            new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId, $moduleKey),
            $entityType,
            $entityPublicId,
            $moduleKey,
        );
    }

    /**
     * @param  list<string>  $entityTypes
     */
    public function registerModule(string $moduleKey, array $entityTypes): void
    {
        $this->indexPort->registerModule($moduleKey, $entityTypes);
    }
}
