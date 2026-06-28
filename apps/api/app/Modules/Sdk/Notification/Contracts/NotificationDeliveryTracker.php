<?php

namespace App\Modules\Sdk\Notification\Contracts;

use App\Modules\Sdk\Notification\Data\NotificationDelivery;

/**
 * Persists and queries per-channel delivery outcomes for notifications.
 */
interface NotificationDeliveryTracker
{
    public function track(string $organizationId, ?string $workspaceId, NotificationDelivery $delivery): NotificationDelivery;

    /**
     * @return list<NotificationDelivery>
     */
    public function listForNotification(string $organizationId, ?string $workspaceId, string $notificationPublicId): array;
}
