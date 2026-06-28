<?php

namespace App\Services\DataRepository;

use App\Modules\Sdk\DataRepository\Data\EntityRecord;
use App\Modules\Sdk\Entity\Data\EntityReferenceBridge;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Support\Tenant\TenantContext;

class EnterpriseEntityRecordSearchIndexer
{
    public function indexRecordBestEffort(EntityRecord $record, EnterpriseScope $scope): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true) || ! app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: $scope,
                entityType: 'enterprise_entity_record',
                entityPublicId: $record->publicId,
                displayName: sprintf('%s.%s', $record->moduleKey, $record->entityKey),
                keywords: implode(' ', array_filter([
                    $record->moduleKey,
                    $record->entityKey,
                    $record->publicId,
                    $record->searchText,
                ])),
                metadata: [
                    'module_key' => $record->moduleKey,
                    'entity_key' => $record->entityKey,
                    'version' => $record->version,
                ],
                entityReference: EntityReferenceBridge::fromEntity(
                    $record->moduleKey,
                    $record->entityKey,
                    $record->publicId,
                ),
                visibility: $record->visibility,
            ));
        } catch (\Throwable) {
        }
    }
}
