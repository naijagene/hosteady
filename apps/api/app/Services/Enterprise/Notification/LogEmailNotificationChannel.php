<?php

namespace App\Services\Enterprise\Notification;

use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use Illuminate\Support\Facades\Log;

class LogEmailNotificationChannel implements NotificationChannelHandler
{
    public function channel(): NotificationChannel
    {
        return NotificationChannel::LogEmail;
    }

    public function deliver(NotificationDeliveryContext $context): NotificationDeliveryStatus
    {
        Log::info('HEOS notification email (stub)', [
            'type' => $context->request->type,
            'title' => $context->request->title,
            'recipient_membership_id' => $context->recipientMembershipId,
        ]);

        return NotificationDeliveryStatus::Delivered;
    }
}
