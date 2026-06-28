<?php

namespace App\Modules\Sdk\Notification\Contracts;

/**
 * Asynchronous notification dispatch queue.
 */
interface NotificationQueue
{
    /**
     * Enqueue a notification for background delivery processing.
     */
    public function enqueue(string $notificationPublicId): void;
}
