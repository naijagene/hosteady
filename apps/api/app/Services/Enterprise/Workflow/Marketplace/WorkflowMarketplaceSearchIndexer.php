<?php

namespace App\Services\Enterprise\Workflow\Marketplace;

use App\Models\WorkflowPackage;
use App\Models\WorkflowPackageInstall;
use App\Models\WorkflowPackageVersion;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Support\Tenant\TenantContext;

class WorkflowMarketplaceSearchIndexer
{
    public function indexPackageBestEffort(WorkflowPackage $package): void
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
                    moduleKey: $package->module_key ?? 'workflow',
                ),
                entityType: 'workflow_package',
                entityPublicId: $package->public_id,
                displayName: $package->name,
                keywords: implode(' ', array_filter([
                    $package->package_key,
                    $package->name,
                    $package->description,
                    ...(is_array($package->tags) ? $package->tags : []),
                    $package->author,
                    $package->module_key,
                ])),
                metadata: [
                    'package_key' => $package->package_key,
                    'status' => $package->status->value,
                    'visibility' => $package->visibility->value,
                    'type' => $package->type->value,
                ],
                entityReference: new EntityReference(
                    type: 'workflow_package',
                    publicId: $package->public_id,
                    moduleKey: $package->module_key ?? 'workflow',
                    label: $package->name,
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }

    public function indexPackageVersionBestEffort(WorkflowPackageVersion $version): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true) || ! app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);
            $package = $version->workflowPackage;

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: $package?->module_key ?? 'workflow',
                ),
                entityType: 'workflow_package_version',
                entityPublicId: $version->public_id,
                displayName: sprintf('%s %s', $package?->name ?? 'Package', $version->version),
                keywords: implode(' ', array_filter([
                    $package?->package_key,
                    $package?->name,
                    $version->version,
                ])),
                metadata: [
                    'package_public_id' => $package?->public_id,
                    'version' => $version->version,
                    'status' => $version->status->value,
                ],
                entityReference: new EntityReference(
                    type: 'workflow_package_version',
                    publicId: $version->public_id,
                    moduleKey: $package?->module_key ?? 'workflow',
                    label: $package?->name ?? 'Package version',
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }

    public function indexInstallBestEffort(WorkflowPackageInstall $install): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true) || ! app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);
            $package = $install->workflowPackage;

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: $package?->module_key ?? 'workflow',
                ),
                entityType: 'workflow_package_install',
                entityPublicId: $install->public_id,
                displayName: sprintf('%s install', $package?->name ?? 'Package'),
                keywords: implode(' ', array_filter([
                    $package?->package_key,
                    $package?->name,
                    $install->installed_version,
                ])),
                metadata: [
                    'package_public_id' => $package?->public_id,
                    'installed_version' => $install->installed_version,
                    'status' => $install->status->value,
                ],
                entityReference: new EntityReference(
                    type: 'workflow_package_install',
                    publicId: $install->public_id,
                    moduleKey: $package?->module_key ?? 'workflow',
                    label: $package?->name ?? 'Installed package',
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }
}
