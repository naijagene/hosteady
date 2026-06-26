<?php

namespace App\Services\Enterprise\Workflow\Runtime;

use App\Models\WorkflowInstance;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\PlatformEventRequest;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;
use App\Services\Enterprise\EventBus\EventBusService;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Support\Tenant\TenantContext;

class WorkflowRuntimeIntegrations
{
    public function indexInstanceBestEffort(TenantContext $context, WorkflowInstance $instance): void
    {
        try {
            $definition = $instance->definition;
            if ($definition === null) {
                return;
            }

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: $definition->module_key ?? 'workflow',
                ),
                entityType: 'workflow_instance',
                entityPublicId: $instance->public_id,
                displayName: sprintf('%s execution', $definition->name),
                keywords: implode(' ', array_filter([
                    $definition->workflow_key,
                    $instance->status->value,
                    $instance->public_id,
                ])),
                metadata: [
                    'workflow_key' => $definition->workflow_key,
                    'status' => $instance->status->value,
                    'definition_public_id' => $definition->public_id,
                ],
                entityReference: new EntityReference(
                    type: 'workflow_instance',
                    publicId: $instance->public_id,
                    moduleKey: $definition->module_key,
                    label: $definition->name,
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }

    public function dispatchExecutionEventBestEffort(TenantContext $context, string $eventName, WorkflowInstance $instance): void
    {
        try {
            if (! (bool) config('heos.enterprise.event_bus.enabled', true)) {
                return;
            }

            app(EventBusService::class)->dispatch($context, new PlatformEventRequest(
                scope: new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: $instance->definition?->module_key ?? 'workflow',
                ),
                eventName: $eventName,
                payload: [
                    'instance_public_id' => $instance->public_id,
                    'status' => $instance->status->value,
                    'definition_public_id' => $instance->definition?->public_id,
                ],
            ));
        } catch (\Throwable) {
        }
    }
}
