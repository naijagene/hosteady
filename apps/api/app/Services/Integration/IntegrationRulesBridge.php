<?php

namespace App\Services\Integration;

use App\Modules\Sdk\Integration\Data\IntegrationEventEnvelope;
use App\Support\Tenant\TenantContext;

class IntegrationRulesBridge
{
    public function emitEventBestEffort(
        TenantContext $context,
        string $eventName,
        array $payload = [],
        ?string $moduleKey = null,
    ): void {
        try {
            if (! (bool) config('heos.enterprise.integrations.enabled', true)) {
                return;
            }

            app(EnterpriseIntegrationEventBusService::class)->publish($context, IntegrationEventEnvelope::fromArray([
                'event_name' => $eventName,
                'direction' => 'internal',
                'source_type' => 'rule',
                'source_module_key' => $moduleKey,
                'payload' => $payload,
                'metadata' => [
                    'bridge' => 'rules',
                ],
            ]));
        } catch (\Throwable) {
        }
    }
}
