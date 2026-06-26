<?php

namespace App\Services\Enterprise\Workflow\Human;

use App\Models\WorkflowHumanTask;
use App\Modules\Sdk\Enterprise\Contracts\NotificationPort;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\NotificationRequest;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Support\Tenant\TenantContext;

class HumanTaskIntegrations
{
    public function indexTaskBestEffort(TenantContext $context, WorkflowHumanTask $task): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true)) {
                return;
            }

            $definition = $task->workflowInstance?->definition;

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: $definition?->module_key ?? 'workflow',
                ),
                entityType: 'workflow_human_task',
                entityPublicId: $task->public_id,
                displayName: $task->title,
                keywords: implode(' ', array_filter([
                    $task->task_type,
                    $task->status->value,
                    $task->title,
                    $task->public_id,
                ])),
                metadata: [
                    'task_type' => $task->task_type,
                    'status' => $task->status->value,
                    'workflow_instance_public_id' => $task->workflowInstance?->public_id,
                ],
                entityReference: new EntityReference(
                    type: 'workflow_human_task',
                    publicId: $task->public_id,
                    moduleKey: $definition?->module_key ?? 'workflow',
                    label: $task->title,
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }

    public function notifyTaskEventBestEffort(TenantContext $context, string $eventName, WorkflowHumanTask $task): void
    {
        try {
            if (! (bool) config('heos.enterprise.notifications.enabled', true)) {
                return;
            }

            $assigneeMembershipPublicId = $task->assigneeMembership?->public_id;

            if ($assigneeMembershipPublicId === null) {
                return;
            }

            app(NotificationPort::class)->notify(new NotificationRequest(
                scope: new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: $task->workflowInstance?->definition?->module_key ?? 'workflow',
                ),
                recipientMembershipPublicId: $assigneeMembershipPublicId,
                type: $eventName,
                title: $task->title,
                body: sprintf('Task [%s] status: %s', $task->title, $task->status->value),
                data: [
                    'task_public_id' => $task->public_id,
                    'task_type' => $task->task_type,
                    'status' => $task->status->value,
                ],
            ));
        } catch (\Throwable) {
        }
    }
}
