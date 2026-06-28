<?php

namespace App\Modules\Sdk\Notification\Contracts;

use App\Modules\Sdk\Notification\Data\NotificationDelivery;
use App\Modules\Sdk\Notification\Data\NotificationReference;
use App\Modules\Sdk\Notification\Data\NotificationRecipient;
use App\Support\Tenant\TenantContext;

/**
 * Channel delivery adapter that transports a notification to a single recipient.
 */
interface NotificationProvider
{
    /**
     * Deliver a notification through the specified channel.
     */
    public function deliver(
        string $channel,
        NotificationReference $notification,
        NotificationRecipient $recipient,
        TenantContext $context,
    ): NotificationDelivery;
}
