<?php

namespace App\Services\Enterprise\Workflow\Designer;

use App\Models\WorkflowCanvasSnapshot;
use App\Models\WorkflowDefinition;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Support\Tenant\TenantContext;

class WorkflowDesignerSearchIndexer
{
    public function indexSnapshotBestEffort(TenantContext $context, WorkflowCanvasSnapshot $snapshot): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true)) {
                return;
            }

            $definition = $snapshot->workflowDefinition;

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: $definition?->module_key ?? 'workflow',
                ),
                entityType: 'workflow_canvas_snapshot',
                entityPublicId: $snapshot->public_id,
                displayName: sprintf('%s canvas', $definition?->name ?? 'Workflow'),
                keywords: implode(' ', array_filter([
                    $snapshot->public_id,
                    $definition?->name,
                    $definition?->workflow_key,
                ])),
                metadata: [
                    'workflow_definition_public_id' => $definition?->public_id,
                    'status' => $snapshot->status->value,
                ],
                entityReference: new EntityReference(
                    type: 'workflow_canvas_snapshot',
                    publicId: $snapshot->public_id,
                    moduleKey: $definition?->module_key ?? 'workflow',
                    label: $definition?->name ?? 'Canvas',
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }

    public function indexDefinitionBestEffort(WorkflowDefinition $definition): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true)) {
                return;
            }

            if (! app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: $definition->module_key ?? 'workflow',
                ),
                entityType: 'workflow_definition',
                entityPublicId: $definition->public_id,
                displayName: $definition->name,
                keywords: implode(' ', array_filter([$definition->workflow_key, $definition->description])),
                metadata: [
                    'workflow_key' => $definition->workflow_key,
                    'status' => $definition->status->value,
                ],
                entityReference: new EntityReference(
                    type: 'workflow_definition',
                    publicId: $definition->public_id,
                    moduleKey: $definition->module_key,
                    label: $definition->name,
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }
}
