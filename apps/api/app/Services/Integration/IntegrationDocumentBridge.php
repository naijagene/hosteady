<?php

namespace App\Services\Integration;

use App\Modules\Sdk\Integration\Data\IntegrationEventEnvelope;
use App\Support\Tenant\TenantContext;

class IntegrationDocumentBridge
{
    public function publishDocumentEventBestEffort(
        TenantContext $context,
        string $eventName,
        string $documentPublicId,
        array $payload = [],
    ): void {
        try {
            if (! (bool) config('heos.enterprise.integrations.enabled', true)) {
                return;
            }

            app(EnterpriseIntegrationEventBusService::class)->publish($context, IntegrationEventEnvelope::fromArray([
                'event_name' => $eventName,
                'direction' => 'internal',
                'source_type' => 'document',
                'source_public_id' => $documentPublicId,
                'payload' => array_merge([
                    'document_public_id' => $documentPublicId,
                ], $payload),
                'metadata' => [
                    'bridge' => 'document',
                ],
            ]));
        } catch (\Throwable) {
        }
    }
}
