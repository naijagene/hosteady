<?php

namespace App\Services\Enterprise\Notification;

use App\Models\PlatformNotification;
use App\Services\Enterprise\Audit\EnterpriseNotificationAuditRecorder;
use App\Support\Tenant\TenantContext;

class NotificationQueryService
{
    public function __construct(
        private readonly EnterpriseNotificationAuditRecorder $auditRecorder,
    ) {
    }

    /**
     * @return list<PlatformNotification>
     */
    public function listForMembership(TenantContext $context, bool $unreadOnly = false, int $limit = 25): array
    {
        $query = PlatformNotification::query()
            ->where('organization_id', $context->organization->id)
            ->where('recipient_membership_id', $context->membership->id)
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        return $query->get()->all();
    }

    public function markRead(TenantContext $context, string $notificationPublicId): PlatformNotification
    {
        $notification = PlatformNotification::query()
            ->where('public_id', $notificationPublicId)
            ->where('organization_id', $context->organization->id)
            ->where('recipient_membership_id', $context->membership->id)
            ->firstOrFail();

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
            $this->auditRecorder->recordRead($notification->fresh(), $context);
        }

        return $notification->fresh();
    }
}
