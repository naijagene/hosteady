<?php

namespace App\Services\Dashboard;

use App\Models\DashboardActivityLog;
use App\Models\DashboardDefinition as DashboardDefinitionModel;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Support\Tenant\TenantContext;

class DynamicDashboardSearchIndexer
{
    public function indexDefinitionBestEffort(DashboardDefinitionModel $definition): void
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
                entityType: 'dashboard_definition',
                entityPublicId: $definition->public_id,
                displayName: $definition->name,
                keywords: implode(' ', array_filter([
                    $definition->module_key,
                    $definition->dashboard_key,
                    $definition->entity_key,
                    $definition->name,
                ])),
                metadata: [
                    'module_key' => $definition->module_key,
                    'dashboard_key' => $definition->dashboard_key,
                    'entity_key' => $definition->entity_key,
                    'type' => $definition->type,
                    'status' => $definition->status,
                ],
                entityReference: new EntityReference(
                    type: 'dashboard_definition',
                    publicId: $definition->public_id,
                    moduleKey: $definition->module_key,
                    label: $definition->name,
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }

    public function indexActivityBestEffort(DashboardActivityLog $activity, EnterpriseScope $scope): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true) || ! app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: $scope,
                entityType: 'dashboard_activity',
                entityPublicId: $activity->public_id,
                displayName: sprintf('Dashboard %s', $activity->action),
                keywords: implode(' ', array_filter([
                    $activity->action,
                    $activity->dashboard_definition_id,
                ])),
                metadata: [
                    'action' => $activity->action,
                    'dashboard_definition_id' => $activity->dashboard_definition_id,
                ],
                entityReference: new EntityReference(
                    type: 'dashboard_activity',
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
