<?php

namespace App\Services\Notification;

use App\Models\EnterpriseNotification;
use App\Modules\Sdk\Notification\Contracts\NotificationQueue;
use App\Modules\Sdk\Notification\Enums\NotificationStatus;

class NotificationQueueService implements NotificationQueue
{
    public function enqueue(string $notificationPublicId): void
    {
        EnterpriseNotification::query()
            ->where('public_id', $notificationPublicId)
            ->update(['status' => NotificationStatus::Queued]);
    }
}
