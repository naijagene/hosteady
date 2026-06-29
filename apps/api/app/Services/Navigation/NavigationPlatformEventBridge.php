<?php

namespace App\Services\Navigation;

use App\Modules\Sdk\Enterprise\Data\PlatformEventRequest;
use App\Services\Enterprise\EventBus\EventBusService;
use App\Support\Tenant\TenantContext;

class NavigationPlatformEventBridge
{
    public function dispatchBestEffort(TenantContext $context, string $eventName, array $payload = []): void
    {
        try {
            if (! app()->bound(EventBusService::class)) {
                return;
            }

            app(EventBusService::class)->dispatch($context, new PlatformEventRequest(
                eventName: $eventName,
                payload: $payload,
            ));
        } catch (\Throwable) {
        }
    }
}
