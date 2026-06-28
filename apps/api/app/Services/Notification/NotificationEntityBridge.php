<?php

namespace App\Services\Notification;

use App\Modules\Sdk\DataRepository\Data\EntityRecordReference;
use App\Modules\Sdk\Notification\Data\NotificationMessage;
use App\Support\Tenant\TenantContext;

class NotificationEntityBridge
{
    public function __construct(
        private readonly EnterpriseNotificationService $notificationService,
    ) {
    }

    public function notifyRecordEventBestEffort(
        TenantContext $context,
        string $eventName,
        EntityRecordReference $record,
        ?string $recipientMembershipPublicId = null,
    ): void {
        try {
            if (! (bool) config('heos.enterprise.notifications.enabled', true)) {
                return;
            }

            $recordName = $record->publicId;

            $this->notificationService->send($context, new NotificationMessage(
                title: sprintf('Record: %s', $recordName),
                body: sprintf('Record [%s] triggered event [%s].', $recordName, $eventName),
                scope: $recipientMembershipPublicId !== null ? 'user' : 'organization',
                priority: 'normal',
                templateKey: null,
                mergeData: [
                    'entity_name' => $record->entityKey,
                    'record_name' => $recordName,
                ],
                channels: ['in_app'],
                recipientMembershipPublicId: $recipientMembershipPublicId ?? $context->membershipPublicId,
                recipientMembershipPublicIds: [],
                rolePublicId: null,
                moduleKey: $record->moduleKey,
                metadata: [
                    'event' => $eventName,
                    'record_public_id' => $record->publicId,
                    'entity_key' => $record->entityKey,
                    'type' => 'entity_record.'.$eventName,
                ],
            ));
        } catch (\Throwable) {
        }
    }
}
