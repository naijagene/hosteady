<?php

namespace App\Services\Enterprise\Workflow\Automation;

use App\Models\Organization;
use App\Models\WorkflowAutomationRule as WorkflowAutomationRuleModel;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowTimer;
use App\Models\WorkflowTriggerExecution;
use App\Models\WorkflowTriggerSubscription;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Automation\Contracts\WorkflowAutomationPort;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowAutomationRule;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowAutomationStatistics;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowTimerReference;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowTriggerReference;
use App\Modules\Sdk\Workflow\Automation\Enums\WorkflowAutomationStatus;
use App\Modules\Sdk\Workflow\Automation\Exceptions\WorkflowAutomationException;
use Illuminate\Support\Facades\DB;

class LaravelWorkflowAutomationAdapter implements WorkflowAutomationPort
{
    public function __construct(
        private readonly WorkflowAutomationStatisticsService $statisticsService,
        private readonly WorkflowAutomationAuditRecorder $auditRecorder,
        private readonly WorkflowScheduledTriggerService $scheduledTriggerService,
    ) {
    }

    /**
     * @return list<WorkflowAutomationRule>
     */
    public function listRules(EnterpriseScope $scope, ?string $status = null, int $limit = 50): array
    {
        $query = $this->scopedRulesQuery($scope)->with('workflowDefinition');

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('created_at')->limit($limit)->get()
            ->map(fn (WorkflowAutomationRuleModel $rule) => $this->toRuleData($rule))
            ->all();
    }

    public function getRule(EnterpriseScope $scope, string $publicId): WorkflowAutomationRule
    {
        return $this->toRuleData($this->findRule($scope, $publicId));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRule(
        EnterpriseScope $scope,
        array $data,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowAutomationRule {
        $organization = Organization::query()
            ->where('public_id', $scope->organizationPublicId)
            ->firstOrFail();

        $workspaceId = null;

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $organization->id)
                ->value('id');
        }

        $definition = WorkflowDefinition::query()
            ->where('public_id', (string) ($data['workflow_definition_public_id'] ?? ''))
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        $triggerType = (string) ($data['trigger_type'] ?? 'manual');
        $triggerConfig = is_array($data['trigger_config'] ?? null) ? $data['trigger_config'] : [];
        $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];

        return DB::transaction(function () use (
            $organization,
            $workspaceId,
            $definition,
            $triggerType,
            $triggerConfig,
            $metadata,
            $userId,
            $membershipId,
        ) {
            $rule = WorkflowAutomationRuleModel::query()->create([
                'organization_id' => $organization->id,
                'workspace_id' => $workspaceId,
                'workflow_definition_id' => $definition->id,
                'trigger_type' => $triggerType,
                'trigger_config' => $triggerConfig,
                'status' => WorkflowAutomationStatus::Active,
                'metadata' => $metadata,
                'created_by_user_id' => $userId,
                'created_by_membership_id' => $membershipId,
            ]);

            $this->syncSubscriptions($rule, $triggerType, $triggerConfig);
            $this->scheduledTriggerService->syncScheduleTask($rule->fresh(['workflowDefinition', 'organization', 'workspace']));

            $this->auditRecorder->recordRuleCreated($rule->fresh(['workflowDefinition']));

            return $this->toRuleData($rule->fresh(['workflowDefinition']));
        });
    }

    public function enableRule(EnterpriseScope $scope, string $publicId): WorkflowAutomationRule
    {
        $rule = $this->findRule($scope, $publicId);
        $rule->update(['status' => WorkflowAutomationStatus::Active]);
        $this->syncSubscriptions($rule->fresh(), $rule->trigger_type, $rule->trigger_config ?? []);
        $this->scheduledTriggerService->syncScheduleTask($rule->fresh(['workflowDefinition', 'organization', 'workspace']));
        $this->auditRecorder->recordRuleEnabled($rule->fresh(['workflowDefinition']));

        return $this->toRuleData($rule->fresh(['workflowDefinition']));
    }

    public function disableRule(EnterpriseScope $scope, string $publicId): WorkflowAutomationRule
    {
        $rule = $this->findRule($scope, $publicId);
        $rule->update(['status' => WorkflowAutomationStatus::Disabled]);
        WorkflowTriggerSubscription::query()
            ->where('workflow_automation_rule_id', $rule->id)
            ->update(['status' => WorkflowAutomationStatus::Disabled]);
        $this->scheduledTriggerService->pauseScheduleTask($rule);
        $this->auditRecorder->recordRuleDisabled($rule->fresh(['workflowDefinition']));

        return $this->toRuleData($rule->fresh(['workflowDefinition']));
    }

    public function deleteRule(EnterpriseScope $scope, string $publicId): void
    {
        $rule = $this->findRule($scope, $publicId);
        $this->scheduledTriggerService->cancelScheduleTask($rule);
        WorkflowTriggerSubscription::query()
            ->where('workflow_automation_rule_id', $rule->id)
            ->delete();
        $this->auditRecorder->recordRuleDeleted($rule);
        $rule->delete();
    }

    /**
     * @return list<WorkflowTriggerReference>
     */
    public function listTriggerExecutions(EnterpriseScope $scope, int $limit = 50): array
    {
        return $this->scopedExecutionsQuery($scope)
            ->with(['automationRule', 'workflowInstance'])
            ->orderByDesc('executed_at')
            ->limit($limit)
            ->get()
            ->map(fn (WorkflowTriggerExecution $execution) => $this->toTriggerReference($execution))
            ->all();
    }

    /**
     * @return list<WorkflowTimerReference>
     */
    public function listTimers(EnterpriseScope $scope, ?string $status = null, int $limit = 50): array
    {
        $query = $this->scopedTimersQuery($scope)->with('workflowInstance');

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->orderBy('due_at')->limit($limit)->get()
            ->map(fn (WorkflowTimer $timer) => $this->toTimerReference($timer))
            ->all();
    }

    public function statistics(EnterpriseScope $scope): WorkflowAutomationStatistics
    {
        $organizationId = Organization::query()
            ->where('public_id', $scope->organizationPublicId)
            ->value('id');

        if ($organizationId === null) {
            return new WorkflowAutomationStatistics(0, 0, 0, 0, 0, 0);
        }

        $workspaceId = null;

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $organizationId)
                ->value('id');
        }

        return $this->statisticsService->statistics($scope, $organizationId, $workspaceId);
    }

    /**
     * @param  array<string, mixed>  $triggerConfig
     */
    private function syncSubscriptions(
        WorkflowAutomationRuleModel $rule,
        string $triggerType,
        array $triggerConfig,
    ): void {
        WorkflowTriggerSubscription::query()
            ->where('workflow_automation_rule_id', $rule->id)
            ->delete();

        if ($rule->status !== WorkflowAutomationStatus::Active) {
            return;
        }

        $eventNames = match ($triggerType) {
            'platform_event' => array_filter([(string) ($triggerConfig['event_name'] ?? '')]),
            'entity_created' => array_filter([(string) ($triggerConfig['event_name'] ?? 'entity.created')]),
            'entity_updated' => array_filter([(string) ($triggerConfig['event_name'] ?? 'entity.updated')]),
            default => [],
        };

        foreach ($eventNames as $eventName) {
            WorkflowTriggerSubscription::query()->create([
                'organization_id' => $rule->organization_id,
                'workspace_id' => $rule->workspace_id,
                'workflow_automation_rule_id' => $rule->id,
                'event_name' => $eventName,
                'status' => WorkflowAutomationStatus::Active,
            ]);
        }
    }

    private function findRule(EnterpriseScope $scope, string $publicId): WorkflowAutomationRuleModel
    {
        $rule = $this->scopedRulesQuery($scope)
            ->with('workflowDefinition')
            ->where('public_id', $publicId)
            ->first();

        if ($rule === null) {
            throw new WorkflowAutomationException('Workflow automation rule not found.');
        }

        return $rule;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<WorkflowAutomationRuleModel>
     */
    private function scopedRulesQuery(EnterpriseScope $scope): \Illuminate\Database\Eloquent\Builder
    {
        $organizationId = Organization::query()
            ->where('public_id', $scope->organizationPublicId)
            ->value('id');

        $query = WorkflowAutomationRuleModel::query()
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at');

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $organizationId)
                ->value('id');

            $query->where(function ($builder) use ($workspaceId) {
                $builder->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        return $query;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<WorkflowTriggerExecution>
     */
    private function scopedExecutionsQuery(EnterpriseScope $scope): \Illuminate\Database\Eloquent\Builder
    {
        $organizationId = Organization::query()
            ->where('public_id', $scope->organizationPublicId)
            ->value('id');

        $query = WorkflowTriggerExecution::query()->where('organization_id', $organizationId);

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $organizationId)
                ->value('id');

            $query->where(function ($builder) use ($workspaceId) {
                $builder->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        return $query;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<WorkflowTimer>
     */
    private function scopedTimersQuery(EnterpriseScope $scope): \Illuminate\Database\Eloquent\Builder
    {
        $organizationId = Organization::query()
            ->where('public_id', $scope->organizationPublicId)
            ->value('id');

        $query = WorkflowTimer::query()->where('organization_id', $organizationId);

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $organizationId)
                ->value('id');

            $query->where(function ($builder) use ($workspaceId) {
                $builder->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        return $query;
    }

    private function toRuleData(WorkflowAutomationRuleModel $rule): WorkflowAutomationRule
    {
        return new WorkflowAutomationRule(
            publicId: $rule->public_id,
            triggerType: $rule->trigger_type,
            status: $rule->status->value,
            workflowDefinitionPublicId: $rule->workflowDefinition->public_id,
            workflowDefinitionName: $rule->workflowDefinition->name,
            triggerConfig: $rule->trigger_config ?? [],
            metadata: $rule->metadata ?? [],
            createdAt: $rule->created_at?->toIso8601String(),
        );
    }

    private function toTriggerReference(WorkflowTriggerExecution $execution): WorkflowTriggerReference
    {
        return new WorkflowTriggerReference(
            publicId: $execution->public_id,
            rulePublicId: $execution->automationRule->public_id,
            triggerSource: $execution->trigger_source->value,
            status: $execution->status->value,
            eventName: $execution->event_name,
            workflowInstancePublicId: $execution->workflowInstance?->public_id,
            errorMessage: $execution->error_message,
            executedAt: $execution->executed_at?->toIso8601String(),
            metadata: $execution->metadata ?? [],
        );
    }

    private function toTimerReference(WorkflowTimer $timer): WorkflowTimerReference
    {
        return new WorkflowTimerReference(
            publicId: $timer->public_id,
            timerType: $timer->timer_type,
            status: $timer->status->value,
            nodeId: $timer->node_id,
            workflowInstancePublicId: $timer->workflowInstance->public_id,
            dueAt: $timer->due_at->toIso8601String(),
            metadata: $timer->metadata ?? [],
            createdAt: $timer->created_at?->toIso8601String(),
        );
    }
}
