<?php

namespace App\Services\Enterprise\Workflow\Automation;

use App\Models\WorkflowAutomationRule;
use App\Models\WorkflowTimer;
use App\Modules\Sdk\Enterprise\Contracts\NotificationPort;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\NotificationRequest;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Support\Tenant\TenantContext;

class WorkflowAutomationIntegrations
{
    public function indexRuleBestEffort(TenantContext $context, WorkflowAutomationRule $rule): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true)) {
                return;
            }

            $definition = $rule->workflowDefinition;

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: $definition?->module_key ?? 'workflow',
                ),
                entityType: 'workflow_automation_rule',
                entityPublicId: $rule->public_id,
                displayName: sprintf('%s automation', $definition?->name ?? $rule->trigger_type),
                keywords: implode(' ', array_filter([
                    $rule->trigger_type,
                    $rule->status->value,
                    $rule->public_id,
                    $definition?->name,
                ])),
                metadata: [
                    'trigger_type' => $rule->trigger_type,
                    'status' => $rule->status->value,
                    'workflow_definition_public_id' => $definition?->public_id,
                ],
                entityReference: new EntityReference(
                    type: 'workflow_automation_rule',
                    publicId: $rule->public_id,
                    moduleKey: $definition?->module_key ?? 'workflow',
                    label: $definition?->name ?? $rule->trigger_type,
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }

    public function notifyRuleEventBestEffort(TenantContext $context, string $eventName, WorkflowAutomationRule $rule): void
    {
        try {
            if (! (bool) config('heos.enterprise.notifications.enabled', true)) {
                return;
            }

            app(NotificationPort::class)->notify(new NotificationRequest(
                scope: new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: $rule->workflowDefinition?->module_key ?? 'workflow',
                ),
                recipientMembershipPublicId: $context->membershipPublicId,
                type: $eventName,
                title: 'Workflow automation updated',
                body: sprintf('Automation rule [%s] is now %s.', $rule->trigger_type, $rule->status->value),
                data: [
                    'rule_public_id' => $rule->public_id,
                    'trigger_type' => $rule->trigger_type,
                    'status' => $rule->status->value,
                ],
            ));
        } catch (\Throwable) {
        }
    }

    public function indexTimerBestEffort(TenantContext $context, WorkflowTimer $timer): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true)) {
                return;
            }

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: 'workflow',
                ),
                entityType: 'workflow_timer',
                entityPublicId: $timer->public_id,
                displayName: sprintf('Timer for node %s', $timer->node_id),
                keywords: implode(' ', array_filter([
                    $timer->timer_type,
                    $timer->status->value,
                    $timer->node_id,
                    $timer->public_id,
                ])),
                metadata: [
                    'timer_type' => $timer->timer_type,
                    'status' => $timer->status->value,
                    'node_id' => $timer->node_id,
                    'due_at' => $timer->due_at?->toIso8601String(),
                ],
                entityReference: new EntityReference(
                    type: 'workflow_timer',
                    publicId: $timer->public_id,
                    moduleKey: 'workflow',
                    label: $timer->node_id,
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }
}
