<?php

namespace App\Services\Notification\Providers;

use App\Modules\Sdk\Enterprise\Contracts\NotificationPort;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\NotificationRequest;
use App\Modules\Sdk\Notification\Contracts\NotificationProvider;
use App\Modules\Sdk\Notification\Data\NotificationDelivery;
use App\Modules\Sdk\Notification\Data\NotificationReference;
use App\Modules\Sdk\Notification\Data\NotificationRecipient;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class InAppProvider implements NotificationProvider
{
    public function __construct(
        private readonly NotificationPort $notificationPort,
    ) {
    }

    public function deliver(
        string $channel,
        NotificationReference $notification,
        NotificationRecipient $recipient,
        TenantContext $context,
    ): NotificationDelivery {
        if ($channel !== 'in_app') {
            return new NotificationDelivery(
                publicId: (string) Str::uuid7(),
                notificationPublicId: $notification->publicId,
                channel: $channel,
                status: 'failed',
                recipientMembershipPublicId: $recipient->membershipPublicId,
                deliveredAt: null,
                metadata: ['provider' => 'in_app', 'error' => 'unsupported_channel'],
            );
        }

        $moduleKey = is_string($notification->metadata['module_key'] ?? null)
            ? $notification->metadata['module_key']
            : null;
        $type = is_string($notification->metadata['type'] ?? null)
            ? $notification->metadata['type']
            : ($notification->templateKey ?? 'enterprise_notification');

        $result = $this->notificationPort->notify(new NotificationRequest(
            scope: new EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
                moduleKey: $moduleKey,
            ),
            recipientMembershipPublicId: $recipient->membershipPublicId,
            type: $type,
            title: $notification->title,
            body: $notification->body,
            data: array_merge($notification->mergeData, $notification->metadata),
            channels: ['in_app'],
        ));

        return new NotificationDelivery(
            publicId: (string) Str::uuid7(),
            notificationPublicId: $notification->publicId,
            channel: 'in_app',
            status: $result->status === 'skipped' ? 'failed' : 'delivered',
            recipientMembershipPublicId: $recipient->membershipPublicId,
            deliveredAt: $result->status === 'skipped' ? null : now()->toIso8601String(),
            metadata: [
                'provider' => 'in_app',
                'platform_notification_public_id' => $result->notificationPublicId,
                'delivered_channels' => $result->deliveredChannels,
            ],
        );
    }
}
