<?php

namespace App\Services\Enterprise\Notification;

use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use App\Models\PlatformNotification;
use Illuminate\Support\Str;

class InAppNotificationChannel implements NotificationChannelHandler
{
    public function channel(): NotificationChannel
    {
        return NotificationChannel::InApp;
    }

    public function deliver(NotificationDeliveryContext $context): NotificationDeliveryStatus
    {
        PlatformNotification::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $context->organizationId,
            'workspace_id' => $context->workspaceId,
            'recipient_membership_id' => $context->recipientMembershipId,
            'module_key' => $context->request->scope->moduleKey,
            'type' => $context->request->type,
            'title' => $context->request->title,
            'body' => $context->request->body,
            'data' => $context->request->data,
            'subject_reference' => $context->subject?->toArray(),
            'channel' => NotificationChannel::InApp->value,
            'status' => NotificationDeliveryStatus::Delivered,
        ]);

        return NotificationDeliveryStatus::Delivered;
    }
}
