<?php

namespace App\Services\Enterprise\Notification;

use App\Models\NotificationPreference;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\PlatformNotification;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Contracts\NotificationPort;
use App\Modules\Sdk\Enterprise\Data\NotificationRequest;
use App\Modules\Sdk\Enterprise\Data\NotificationResult;
use App\Services\Enterprise\Audit\EnterpriseNotificationAuditRecorder;
use Illuminate\Support\Str;

class LaravelNotificationAdapter implements NotificationPort
{
    /**
     * @param  array<string, NotificationChannelHandler>  $channels
     */
    public function __construct(
        private readonly array $channels,
        private readonly EnterpriseNotificationAuditRecorder $auditRecorder,
    ) {
    }

    public function notify(NotificationRequest $request): NotificationResult
    {
        $organization = Organization::query()
            ->where('public_id', $request->scope->organizationPublicId)
            ->firstOrFail();

        $workspaceId = null;

        if ($request->scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $request->scope->workspacePublicId)
                ->where('organization_id', $organization->id)
                ->value('id');
        }

        $membership = OrganizationMembership::query()
            ->where('public_id', $request->recipientMembershipPublicId)
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        $deliveryContext = new NotificationDeliveryContext(
            request: $request,
            organizationId: $organization->id,
            workspaceId: $workspaceId,
            recipientMembershipId: $membership->id,
            subject: $request->subject,
        );

        $deliveredChannels = [];
        $notificationPublicId = (string) Str::uuid7();

        foreach ($request->channels as $channelName) {
            if (! $this->isChannelEnabled($membership->id, $organization->id, $request->type, $channelName)) {
                continue;
            }

            $handler = $this->channels[$channelName] ?? null;

            if ($handler === null) {
                continue;
            }

            $status = $handler->deliver($deliveryContext);

            if ($status === \App\Enums\NotificationDeliveryStatus::Delivered) {
                $deliveredChannels[] = $channelName;
            }
        }

        $notification = PlatformNotification::query()
            ->where('recipient_membership_id', $membership->id)
            ->latest('created_at')
            ->first();

        if ($notification !== null) {
            $notificationPublicId = $notification->public_id;
            $this->auditRecorder->recordSent($notification);
        }

        return new NotificationResult(
            notificationPublicId: $notificationPublicId,
            deliveredChannels: $deliveredChannels,
            status: $deliveredChannels === [] ? 'skipped' : 'delivered',
        );
    }

    private function isChannelEnabled(string $membershipId, string $organizationId, string $type, string $channel): bool
    {
        $preference = NotificationPreference::query()
            ->where('membership_id', $membershipId)
            ->where('organization_id', $organizationId)
            ->where('channel', $channel)
            ->where('type', $type)
            ->first();

        return $preference?->enabled ?? true;
    }
}
