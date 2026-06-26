<?php

namespace App\Services\Enterprise\Workflow;

use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;
use App\Models\WorkflowDefinition;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Support\Tenant\TenantContext;

class WorkflowSearchIndexer
{
    public function __construct(
        private readonly SearchIndexService $searchIndexService,
    ) {
    }

    public function indexBestEffort(TenantContext $context, WorkflowDefinition $definition): void
    {
        try {
            $this->searchIndexService->upsert($context, new SearchIndexUpsertRequest(
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
