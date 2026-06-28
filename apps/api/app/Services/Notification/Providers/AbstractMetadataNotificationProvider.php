<?php

namespace App\Services\Notification\Providers;

use App\Modules\Sdk\Notification\Contracts\NotificationProvider;
use App\Modules\Sdk\Notification\Data\NotificationDelivery;
use App\Modules\Sdk\Notification\Data\NotificationReference;
use App\Modules\Sdk\Notification\Data\NotificationRecipient;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

abstract class AbstractMetadataNotificationProvider implements NotificationProvider
{
    abstract protected function providerName(): string;

    abstract protected function supportedChannel(): string;

    public function deliver(
        string $channel,
        NotificationReference $notification,
        NotificationRecipient $recipient,
        TenantContext $context,
    ): NotificationDelivery {
        if ($channel !== $this->supportedChannel()) {
            return new NotificationDelivery(
                publicId: (string) Str::uuid7(),
                notificationPublicId: $notification->publicId,
                channel: $channel,
                status: 'failed',
                recipientMembershipPublicId: $recipient->membershipPublicId,
                deliveredAt: null,
                metadata: [
                    'provider' => $this->providerName(),
                    'error' => 'unsupported_channel',
                ],
            );
        }

        return new NotificationDelivery(
            publicId: (string) Str::uuid7(),
            notificationPublicId: $notification->publicId,
            channel: $channel,
            status: 'delivered',
            recipientMembershipPublicId: $recipient->membershipPublicId,
            deliveredAt: now()->toIso8601String(),
            metadata: [
                'provider' => $this->providerName(),
                'simulated' => true,
            ],
        );
    }
}
