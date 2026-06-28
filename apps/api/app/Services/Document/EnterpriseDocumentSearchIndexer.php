<?php

namespace App\Services\Document;

use App\Modules\Sdk\Document\Data\DocumentReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Support\Tenant\TenantContext;

class EnterpriseDocumentSearchIndexer
{
    public function indexDocumentBestEffort(DocumentReference $document, TenantContext $context): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true)) {
                return;
            }

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: $document->moduleKey,
                ),
                entityType: 'enterprise_document',
                entityPublicId: $document->publicId,
                displayName: $document->title,
                keywords: implode(' ', array_filter([
                    $document->title,
                    $document->description,
                    $document->publicId,
                    $document->category,
                    $document->moduleKey,
                ])),
                metadata: [
                    'category' => $document->category,
                    'status' => $document->status,
                    'current_version_number' => $document->currentVersionNumber,
                ],
                entityReference: null,
                visibility: $document->visibility,
            ));
        } catch (\Throwable) {
        }
    }
}
