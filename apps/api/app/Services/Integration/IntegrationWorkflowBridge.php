<?php

namespace App\Services\Integration;

use App\Modules\Sdk\Integration\Data\IntegrationEventEnvelope;
use App\Support\Tenant\TenantContext;

class IntegrationWorkflowBridge
{
    public function publishWorkflowEventBestEffort(
        TenantContext $context,
        string $eventName,
        ?string $workflowPublicId = null,
        array $payload = [],
    ): void {
        try {
            if (! (bool) config('heos.enterprise.integrations.enabled', true)) {
                return;
            }

            app(EnterpriseIntegrationEventBusService::class)->publish($context, IntegrationEventEnvelope::fromArray([
                'event_name' => $eventName,
                'direction' => 'internal',
                'source_type' => 'workflow',
                'source_public_id' => $workflowPublicId,
                'payload' => $payload,
                'metadata' => [
                    'bridge' => 'workflow',
                ],
            ]));
        } catch (\Throwable) {
        }
    }
}
