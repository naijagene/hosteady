<?php

namespace App\Services\Enterprise\Workflow\Automation;

use App\Models\Organization;
use App\Models\WorkflowInstance;
use App\Models\WorkflowTimer;
use App\Models\WorkflowTimerExecution;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Contracts\ModuleEventSubscriber;
use App\Modules\Sdk\Enterprise\Data\PlatformEventData;
use App\Modules\Sdk\Workflow\Automation\Contracts\WorkflowEventTriggerProvider;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowAutomationRule;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowEventSubscription;
use App\Modules\Sdk\Workflow\Automation\Enums\WorkflowAutomationStatus;
use App\Modules\Sdk\Workflow\Automation\Enums\WorkflowTriggerSource;
use App\Models\WorkflowTriggerSubscription;

class WorkflowEventTriggerService implements ModuleEventSubscriber, WorkflowEventTriggerProvider
{
    public function __construct(
        private readonly WorkflowTriggerService $triggerService,
    ) {
    }

    /**
     * @return list<string>
     */
    public function subscribedEvents(): array
    {
        if (! (bool) config('heos.enterprise.automation.enabled', true)) {
            return [];
        }

        return WorkflowTriggerSubscription::query()
            ->where('status', WorkflowAutomationStatus::Active)
            ->distinct()
            ->pluck('event_name')
            ->all();
    }

    public function handle(PlatformEventData $event): void
    {
        if (! (bool) config('heos.enterprise.automation.enabled', true)) {
            return;
        }

        foreach ($this->subscriptionsForEvent($event) as $subscription) {
            try {
                $this->executeSubscription($subscription, $event);
            } catch (\Throwable) {
                // Failures must not break event processing.
            }
        }
    }

    /**
     * @return list<WorkflowEventSubscription>
     */
    public function subscriptionsForEvent(PlatformEventData $event): array
    {
        $organizationId = Organization::query()
            ->where('public_id', $event->scope->organizationPublicId)
            ->value('id');

        if ($organizationId === null) {
            return [];
        }

        $query = WorkflowTriggerSubscription::query()
            ->with(['automationRule.workflowDefinition', 'organization', 'workspace'])
            ->where('organization_id', $organizationId)
            ->where('event_name', $event->eventName)
            ->where('status', WorkflowAutomationStatus::Active)
            ->whereHas('automationRule', function ($builder) {
                $builder
                    ->where('status', WorkflowAutomationStatus::Active)
                    ->whereNull('deleted_at');
            });

        if ($event->scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $event->scope->workspacePublicId)
                ->where('organization_id', $organizationId)
                ->value('id');

            $query->where(function ($builder) use ($workspaceId) {
                $builder->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        return $query->get()->map(function (WorkflowTriggerSubscription $subscription) {
            return new WorkflowEventSubscription(
                publicId: $subscription->public_id,
                rulePublicId: $subscription->automationRule->public_id,
                eventName: $subscription->event_name,
                status: $subscription->status->value,
                organizationPublicId: $subscription->organization->public_id,
                workspacePublicId: $subscription->workspace?->public_id,
                metadata: $subscription->metadata ?? [],
            );
        })->all();
    }

    private function executeSubscription(WorkflowEventSubscription $subscription, PlatformEventData $event): void
    {
        $ruleModel = \App\Models\WorkflowAutomationRule::query()
            ->with('workflowDefinition')
            ->where('public_id', $subscription->rulePublicId)
            ->firstOrFail();

        $rule = new WorkflowAutomationRule(
            publicId: $ruleModel->public_id,
            triggerType: $ruleModel->trigger_type,
            status: $ruleModel->status->value,
            workflowDefinitionPublicId: $ruleModel->workflowDefinition->public_id,
            workflowDefinitionName: $ruleModel->workflowDefinition->name,
            triggerConfig: $ruleModel->trigger_config ?? [],
            metadata: $ruleModel->metadata ?? [],
        );

        $this->triggerService->executeRule(
            $rule,
            WorkflowTriggerSource::PlatformEvent->value,
            $event,
        );
    }
}
