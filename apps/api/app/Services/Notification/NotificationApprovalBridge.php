<?php

namespace App\Services\Notification;

use App\Modules\Sdk\Notification\Data\NotificationMessage;
use App\Modules\Sdk\Workflow\Human\Data\ApprovalReference;
use App\Support\Tenant\TenantContext;

class NotificationApprovalBridge
{
    public function __construct(
        private readonly EnterpriseNotificationService $notificationService,
    ) {
    }

    public function notifyApprovalEventBestEffort(
        TenantContext $context,
        string $eventName,
        ApprovalReference $approval,
        ?string $recipientMembershipPublicId = null,
    ): void {
        try {
            if (! (bool) config('heos.enterprise.notifications.enabled', true)) {
                return;
            }

            $approvalName = $approval->title ?? $approval->publicId;

            $this->notificationService->send($context, new NotificationMessage(
                title: sprintf('Approval: %s', $approvalName),
                body: sprintf('Approval [%s] triggered event [%s].', $approvalName, $eventName),
                scope: $recipientMembershipPublicId !== null ? 'user' : 'organization',
                priority: 'high',
                templateKey: null,
                mergeData: [
                    'approval_name' => $approvalName,
                ],
                channels: ['in_app'],
                recipientMembershipPublicId: $recipientMembershipPublicId ?? $context->membershipPublicId,
                recipientMembershipPublicIds: [],
                rolePublicId: null,
                moduleKey: 'workflow',
                metadata: [
                    'event' => $eventName,
                    'approval_public_id' => $approval->publicId,
                    'type' => 'approval.'.$eventName,
                ],
            ));
        } catch (\Throwable) {
        }
    }
}
