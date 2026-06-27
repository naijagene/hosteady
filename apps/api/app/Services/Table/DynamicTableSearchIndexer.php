<?php

namespace App\Services\Table;

use App\Models\TableActivityLog;
use App\Models\TableDefinition as TableDefinitionModel;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Support\Tenant\TenantContext;

class DynamicTableSearchIndexer
{
    public function indexDefinitionBestEffort(TableDefinitionModel $definition): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true) || ! app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: $definition->module_key,
                ),
                entityType: 'table_definition',
                entityPublicId: $definition->public_id,
                displayName: $definition->name,
                keywords: implode(' ', array_filter([
                    $definition->module_key,
                    $definition->table_key,
                    $definition->entity_key,
                    $definition->name,
                ])),
                metadata: [
                    'module_key' => $definition->module_key,
                    'table_key' => $definition->table_key,
                    'entity_key' => $definition->entity_key,
                    'type' => $definition->type,
                    'status' => $definition->status,
                ],
                entityReference: new EntityReference(
                    type: 'table_definition',
                    publicId: $definition->public_id,
                    moduleKey: $definition->module_key,
                    label: $definition->name,
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }

    public function indexActivityBestEffort(TableActivityLog $activity, EnterpriseScope $scope): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true) || ! app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: $scope,
                entityType: 'table_activity',
                entityPublicId: $activity->public_id,
                displayName: sprintf('Table %s', $activity->action),
                keywords: implode(' ', array_filter([
                    $activity->action,
                    $activity->table_definition_id,
                ])),
                metadata: [
                    'action' => $activity->action,
                    'table_definition_id' => $activity->table_definition_id,
                ],
                entityReference: new EntityReference(
                    type: 'table_activity',
                    publicId: $activity->public_id,
                    moduleKey: null,
                    label: $activity->action,
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }
}
