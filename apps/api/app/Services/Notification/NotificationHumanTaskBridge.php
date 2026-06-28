<?php

namespace App\Services\Notification;

use App\Models\WorkflowHumanTask;
use App\Modules\Sdk\Notification\Data\NotificationMessage;
use App\Support\Tenant\TenantContext;

class NotificationHumanTaskBridge
{
    public function __construct(
        private readonly EnterpriseNotificationService $notificationService,
    ) {
    }

    public function notifyTaskEventBestEffort(
        TenantContext $context,
        string $eventName,
        WorkflowHumanTask $task,
    ): void {
        try {
            if (! (bool) config('heos.enterprise.notifications.enabled', true)) {
                return;
            }

            $assigneeMembershipPublicId = $task->assigneeMembership?->public_id;

            if ($assigneeMembershipPublicId === null) {
                return;
            }

            $this->notificationService->send($context, new NotificationMessage(
                title: $task->title,
                body: sprintf('Task [%s] status: %s', $task->title, $task->status->value),
                scope: 'user',
                priority: 'high',
                templateKey: null,
                mergeData: [
                    'task_name' => $task->title,
                ],
                channels: ['in_app'],
                recipientMembershipPublicId: $assigneeMembershipPublicId,
                recipientMembershipPublicIds: [],
                rolePublicId: null,
                moduleKey: $task->workflowInstance?->definition?->module_key ?? 'workflow',
                metadata: [
                    'event' => $eventName,
                    'task_public_id' => $task->public_id,
                    'type' => 'human_task.'.$eventName,
                ],
            ));
        } catch (\Throwable) {
        }
    }
}
