<?php

namespace App\Services\Integration;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\PlatformEventRequest;
use App\Modules\Sdk\Integration\Data\IntegrationEvent;
use App\Modules\Sdk\Integration\Data\IntegrationEventEnvelope;
use App\Services\Enterprise\EventBus\EventBusService;
use App\Support\Tenant\TenantContext;

class IntegrationEventBusBridge
{
    public function forwardToPlatformEventBusBestEffort(TenantContext $context, IntegrationEvent $event): void
    {
        try {
            if (! (bool) config('heos.enterprise.event_bus.enabled', true)) {
                return;
            }

            if (! app()->bound(EventBusService::class)) {
                return;
            }

            app(EventBusService::class)->dispatch($context, new PlatformEventRequest(
                scope: new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: $event->sourceModuleKey ?? 'integration',
                ),
                eventName: $event->eventName,
                payload: array_merge($event->payload, [
                    'integration_event_public_id' => $event->publicId,
                    'integration_status' => $event->status,
                ]),
                correlationId: $event->correlationId,
            ));
        } catch (\Throwable) {
        }
    }

    public function publishFromPlatformEventBestEffort(TenantContext $context, PlatformEventRequest $request): void
    {
        try {
            if (! (bool) config('heos.enterprise.integrations.enabled', true)) {
                return;
            }

            if (($request->payload['integration_event_public_id'] ?? null) !== null) {
                return;
            }

            if (! app()->bound(EnterpriseIntegrationEventBusService::class)) {
                return;
            }

            app(EnterpriseIntegrationEventBusService::class)->publish($context, IntegrationEventEnvelope::fromArray([
                'event_name' => $request->eventName,
                'direction' => 'internal',
                'source_type' => 'system',
                'source_module_key' => $request->scope->moduleKey,
                'correlation_id' => $request->correlationId,
                'payload' => $request->payload,
                'metadata' => [
                    'platform_event' => true,
                ],
            ]));
        } catch (\Throwable) {
        }
    }
}
