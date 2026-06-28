<?php

namespace App\Services\Notification;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\PlatformEventRequest;
use App\Modules\Sdk\Notification\Data\NotificationMessage;
use App\Services\Enterprise\EventBus\EventBusService;
use App\Support\Tenant\TenantContext;

class NotificationWorkflowBridge
{
    public function __construct(
        private readonly EnterpriseNotificationService $notificationService,
    ) {
    }

    public function notifyWorkflowEventBestEffort(
        TenantContext $context,
        string $eventName,
        string $title,
        string $body,
        array $mergeData = [],
        array $metadata = [],
        ?string $recipientMembershipPublicId = null,
    ): void {
        try {
            if (! (bool) config('heos.enterprise.notifications.enabled', true)) {
                return;
            }

            $this->notificationService->send($context, new NotificationMessage(
                title: $title,
                body: $body,
                scope: $recipientMembershipPublicId !== null ? 'user' : 'workspace',
                priority: 'normal',
                templateKey: null,
                mergeData: $mergeData,
                channels: ['in_app'],
                recipientMembershipPublicId: $recipientMembershipPublicId,
                recipientMembershipPublicIds: [],
                rolePublicId: null,
                moduleKey: $metadata['module_key'] ?? 'workflow',
                metadata: array_merge($metadata, ['event' => $eventName]),
            ));
        } catch (\Throwable) {
        }
    }

    public function triggerEventBusBestEffort(
        TenantContext $context,
        string $eventName,
        array $payload = [],
        ?EnterpriseScope $scope = null,
    ): void {
        try {
            if (! app()->bound(EventBusService::class)) {
                return;
            }

            $scope ??= new EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
                moduleKey: is_string($payload['module_key'] ?? null) ? $payload['module_key'] : 'workflow',
            );

            app(EventBusService::class)->dispatch($context, new PlatformEventRequest(
                scope: $scope,
                eventName: $eventName,
                payload: $payload,
            ));
        } catch (\Throwable) {
        }
    }
}
