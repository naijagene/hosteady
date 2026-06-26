<?php

namespace App\Services\Enterprise\Notification;

use App\Modules\Sdk\Enterprise\Contracts\NotificationPort;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\NotificationRequest;
use App\Modules\Sdk\Enterprise\Data\NotificationResult;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;

class NotificationService
{
    public function __construct(
        private readonly NotificationPort $notificationPort,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
        private readonly NotificationQueryService $queryService,
    ) {
    }

    public function notify(TenantContext $context, NotificationRequest $request): NotificationResult
    {
        $this->runtimeBridge->requireCapability($context, 'notifications');

        return $this->notificationPort->notify(new NotificationRequest(
            scope: new EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
                moduleKey: $request->scope->moduleKey,
            ),
            recipientMembershipPublicId: $request->recipientMembershipPublicId,
            type: $request->type,
            title: $request->title,
            body: $request->body,
            data: $request->data,
            subject: $request->subject,
            channels: $request->channels,
        ));
    }

    /**
     * @return list<\App\Models\PlatformNotification>
     */
    public function listUnread(TenantContext $context, int $limit = 25): array
    {
        return $this->queryService->listForMembership($context, unreadOnly: true, limit: $limit);
    }

    public function markRead(TenantContext $context, string $notificationPublicId): \App\Models\PlatformNotification
    {
        return $this->queryService->markRead($context, $notificationPublicId);
    }
}
