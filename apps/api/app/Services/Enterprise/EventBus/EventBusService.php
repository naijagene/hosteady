<?php

namespace App\Services\Enterprise\EventBus;

use App\Modules\Sdk\Enterprise\Contracts\EventBusPort;
use App\Modules\Sdk\Enterprise\Data\PlatformEventRequest;
use App\Modules\Sdk\Enterprise\Data\PlatformEventResult;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;

class EventBusService
{
    public function __construct(
        private readonly EventBusPort $eventBusPort,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
    ) {
    }

    public function dispatch(TenantContext $context, PlatformEventRequest $request): PlatformEventResult
    {
        $this->runtimeBridge->requireCapability($context, 'events');

        $scopedRequest = $this->scopedRequest($context, $request);

        if ($scopedRequest->async || (bool) config('heos.enterprise.event_bus.async', false)) {
            return $this->eventBusPort->dispatchAsync($scopedRequest);
        }

        return $this->eventBusPort->dispatch($scopedRequest);
    }

    private function scopedRequest(TenantContext $context, PlatformEventRequest $request): PlatformEventRequest
    {
        return new PlatformEventRequest(
            scope: new \App\Modules\Sdk\Enterprise\Data\EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
                moduleKey: $request->scope->moduleKey,
            ),
            eventName: $request->eventName,
            payload: $request->payload,
            subject: $request->subject,
            correlationId: $request->correlationId,
            async: $request->async,
        );
    }
}
