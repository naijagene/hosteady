<?php

namespace App\Services\Table;

use App\Models\TableDefinition as TableDefinitionModel;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\PlatformEventRequest;
use App\Services\Enterprise\EventBus\EventBusService;
use App\Support\Tenant\TenantContext;

class DynamicTableWorkflowBridge
{
    public function triggerBestEffort(
        TenantContext $context,
        string $eventName,
        array $payload = [],
        ?EnterpriseScope $scope = null,
    ): void {
        try {
            if (! app()->bound(EventBusService::class)) {
                return;
            }

            $scope ??= new EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
                moduleKey: is_string($payload['module_key'] ?? null) ? $payload['module_key'] : null,
            );

            app(EventBusService::class)->dispatch($context, new PlatformEventRequest(
                scope: $scope,
                eventName: $eventName,
                payload: $payload,
            ));
        } catch (\Throwable) {
        }
    }

    public function triggerDefinitionRegisteredBestEffort(TableDefinitionModel $definition): void
    {
        if (! app()->bound(TenantContext::class)) {
            return;
        }

        $this->triggerBestEffort(
            app(TenantContext::class),
            'table.definition.registered',
            [
                'module_key' => $definition->module_key,
                'table_key' => $definition->table_key,
                'entity_key' => $definition->entity_key,
                'public_id' => $definition->public_id,
            ],
        );
    }

    public function triggerDefinitionUpdatedBestEffort(TableDefinitionModel $definition): void
    {
        if (! app()->bound(TenantContext::class)) {
            return;
        }

        $this->triggerBestEffort(
            app(TenantContext::class),
            'table.definition.updated',
            [
                'module_key' => $definition->module_key,
                'table_key' => $definition->table_key,
                'entity_key' => $definition->entity_key,
                'public_id' => $definition->public_id,
            ],
        );
    }
}
