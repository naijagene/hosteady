<?php

namespace App\Services\Dashboard;

use App\Models\DashboardDefinition as DashboardDefinitionModel;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\PlatformEventRequest;
use App\Services\Enterprise\EventBus\EventBusService;
use App\Support\Tenant\TenantContext;

class DynamicDashboardWorkflowBridge
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

    public function triggerDefinitionRegisteredBestEffort(DashboardDefinitionModel $definition): void
    {
        if (! app()->bound(TenantContext::class)) {
            return;
        }

        $this->triggerBestEffort(
            app(TenantContext::class),
            'dashboard.definition.registered',
            [
                'module_key' => $definition->module_key,
                'dashboard_key' => $definition->dashboard_key,
                'entity_key' => $definition->entity_key,
                'public_id' => $definition->public_id,
            ],
        );
    }

    public function triggerDefinitionUpdatedBestEffort(DashboardDefinitionModel $definition): void
    {
        if (! app()->bound(TenantContext::class)) {
            return;
        }

        $this->triggerBestEffort(
            app(TenantContext::class),
            'dashboard.definition.updated',
            [
                'module_key' => $definition->module_key,
                'dashboard_key' => $definition->dashboard_key,
                'entity_key' => $definition->entity_key,
                'public_id' => $definition->public_id,
            ],
        );
    }
}
