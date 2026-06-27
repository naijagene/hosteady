<?php

namespace App\Services\Module\Development;

use App\Models\BusinessModule;
use App\Models\BusinessModuleInstallation;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Support\Tenant\TenantContext;

class BusinessModuleSearchIndexer
{
    public function indexModuleBestEffort(BusinessModule $module): void
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
                    moduleKey: $module->module_key,
                ),
                entityType: 'business_module',
                entityPublicId: $module->public_id,
                displayName: $module->name,
                keywords: implode(' ', array_filter([$module->module_key, $module->name, $module->description])),
                metadata: [
                    'module_key' => $module->module_key,
                    'status' => $module->status->value,
                    'type' => $module->type->value,
                    'version' => $module->version,
                ],
                entityReference: new EntityReference(
                    type: 'business_module',
                    publicId: $module->public_id,
                    moduleKey: $module->module_key,
                    label: $module->name,
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }

    public function indexInstallationBestEffort(BusinessModuleInstallation $installation): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true) || ! app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);
            $module = $installation->businessModule;

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: $module?->module_key ?? 'business',
                ),
                entityType: 'business_module_installation',
                entityPublicId: $installation->public_id,
                displayName: sprintf('%s installation', $module?->name ?? 'Business module'),
                keywords: implode(' ', array_filter([
                    $module?->module_key,
                    $module?->name,
                    $installation->installed_version,
                ])),
                metadata: [
                    'module_public_id' => $module?->public_id,
                    'installed_version' => $installation->installed_version,
                    'status' => $installation->status->value,
                ],
                entityReference: new EntityReference(
                    type: 'business_module_installation',
                    publicId: $installation->public_id,
                    moduleKey: $module?->module_key ?? 'business',
                    label: $module?->name ?? 'Installation',
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }
}
