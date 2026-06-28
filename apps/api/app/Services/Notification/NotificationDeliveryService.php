<?php

namespace App\Services\Notification;

use App\Models\NotificationDelivery as NotificationDeliveryModel;
use App\Models\OrganizationMembership;
use App\Modules\Sdk\Notification\Contracts\NotificationDeliveryTracker;
use App\Modules\Sdk\Notification\Data\NotificationDelivery;
use Illuminate\Support\Str;

class NotificationDeliveryService implements NotificationDeliveryTracker
{
    public function track(string $organizationId, ?string $workspaceId, NotificationDelivery $delivery): NotificationDelivery
    {
        $membershipId = OrganizationMembership::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $delivery->recipientMembershipPublicId)
            ->value('id');

        $notificationModelId = \App\Models\EnterpriseNotification::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $delivery->notificationPublicId)
            ->value('id');

        $model = NotificationDeliveryModel::query()->firstOrNew(['public_id' => $delivery->publicId]);

        if (! $model->exists) {
            $model->id = (string) Str::uuid7();
        }

        $model->fill([
            'enterprise_notification_id' => $notificationModelId,
            'notification_public_id' => $delivery->notificationPublicId,
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'recipient_membership_id' => $membershipId,
            'channel' => $delivery->channel,
            'status' => $delivery->status,
            'delivered_at' => $delivery->deliveredAt,
            'metadata' => $delivery->metadata,
        ]);
        $model->save();

        return NotificationMapper::toDelivery($model->fresh(['recipientMembership']));
    }

    /**
     * @return list<NotificationDelivery>
     */
    public function listForNotification(string $organizationId, ?string $workspaceId, string $notificationPublicId): array
    {
        $query = NotificationDeliveryModel::query()
            ->with('recipientMembership')
            ->where('organization_id', $organizationId)
            ->where('notification_public_id', $notificationPublicId);

        if ($workspaceId !== null) {
            $query->where(function ($scoped) use ($workspaceId) {
                $scoped->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        return $query->orderByDesc('created_at')
            ->get()
            ->map(fn (NotificationDeliveryModel $model) => NotificationMapper::toDelivery($model))
            ->all();
    }
}
