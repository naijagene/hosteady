<?php

namespace App\Services\Enterprise\Notification;

use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;

interface NotificationChannelHandler
{
    public function channel(): NotificationChannel;

    public function deliver(NotificationDeliveryContext $context): NotificationDeliveryStatus;
}
