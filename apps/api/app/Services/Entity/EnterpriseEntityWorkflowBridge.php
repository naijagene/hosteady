<?php

namespace App\Services\Entity;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\PlatformEventRequest;
use App\Services\Enterprise\EventBus\EventBusService;
use App\Support\Tenant\TenantContext;

class EnterpriseEntityWorkflowBridge
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
}
