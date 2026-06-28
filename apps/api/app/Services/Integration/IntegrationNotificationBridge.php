<?php

namespace App\Services\Integration;

use App\Modules\Sdk\Integration\Data\IntegrationEventEnvelope;
use App\Modules\Sdk\Notification\Data\NotificationReference;
use App\Support\Tenant\TenantContext;

class IntegrationNotificationBridge
{
    public function publishNotificationEventBestEffort(
        TenantContext $context,
        string $eventName,
        NotificationReference $notification,
    ): void {
        try {
            if (! (bool) config('heos.enterprise.integrations.enabled', true)) {
                return;
            }

            app(EnterpriseIntegrationEventBusService::class)->publish($context, IntegrationEventEnvelope::fromArray([
                'event_name' => $eventName,
                'direction' => 'internal',
                'source_type' => 'notification',
                'source_module_key' => $notification->metadata['module_key'] ?? null,
                'source_public_id' => $notification->publicId,
                'payload' => [
                    'notification_public_id' => $notification->publicId,
                    'title' => $notification->title,
                    'status' => $notification->status,
                    'channels' => $notification->channels,
                ],
                'metadata' => [
                    'bridge' => 'notification',
                    'type' => $notification->metadata['type'] ?? null,
                ],
            ]));
        } catch (\Throwable) {
        }
    }
}
