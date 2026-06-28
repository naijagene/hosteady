<?php

namespace App\Services\Integration;

use App\Modules\Sdk\DataRepository\Data\EntityRecordReference;
use App\Modules\Sdk\Integration\Data\IntegrationEventEnvelope;
use App\Support\Tenant\TenantContext;

class IntegrationEntityBridge
{
    public function publishRecordEventBestEffort(
        TenantContext $context,
        string $action,
        EntityRecordReference $record,
        array $payload = [],
    ): void {
        try {
            if (! (bool) config('heos.enterprise.integrations.enabled', true)) {
                return;
            }

            app(EnterpriseIntegrationEventBusService::class)->publish($context, IntegrationEventEnvelope::fromArray([
                'event_name' => 'data.record.'.$action,
                'direction' => 'internal',
                'source_type' => 'data',
                'source_module_key' => $record->moduleKey,
                'source_entity_key' => $record->entityKey,
                'source_public_id' => $record->publicId,
                'payload' => array_merge([
                    'record_public_id' => $record->publicId,
                    'entity_key' => $record->entityKey,
                    'status' => $record->status,
                ], $payload),
                'metadata' => [
                    'bridge' => 'entity',
                    'action' => $action,
                ],
            ]));
        } catch (\Throwable) {
        }
    }
}
