<?php

namespace App\Services\Notification;

use App\Modules\Sdk\Document\Data\DocumentReference;
use App\Modules\Sdk\Notification\Data\NotificationMessage;
use App\Support\Tenant\TenantContext;

class NotificationDocumentBridge
{
    public function __construct(
        private readonly EnterpriseNotificationService $notificationService,
    ) {
    }

    public function notifyDocumentEventBestEffort(
        TenantContext $context,
        string $eventName,
        DocumentReference $document,
        ?string $recipientMembershipPublicId = null,
    ): void {
        try {
            if (! (bool) config('heos.enterprise.notifications.enabled', true)) {
                return;
            }

            $this->notificationService->send($context, new NotificationMessage(
                title: sprintf('Document: %s', $document->title),
                body: sprintf('Document [%s] triggered event [%s].', $document->title, $eventName),
                scope: $recipientMembershipPublicId !== null ? 'user' : 'organization',
                priority: 'normal',
                templateKey: null,
                mergeData: [
                    'document_name' => $document->title,
                ],
                channels: ['in_app'],
                recipientMembershipPublicId: $recipientMembershipPublicId ?? $context->membershipPublicId,
                recipientMembershipPublicIds: [],
                rolePublicId: null,
                moduleKey: $document->moduleKey,
                metadata: [
                    'event' => $eventName,
                    'document_public_id' => $document->publicId,
                    'type' => 'document.'.$eventName,
                ],
            ));
        } catch (\Throwable) {
        }
    }
}
