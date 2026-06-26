<?php

namespace App\Services\Enterprise\Workflow\Automation;

use App\Models\PlatformJob;
use App\Models\ScheduledTask;
use App\Models\WorkflowAutomationRule as WorkflowAutomationRuleModel;
use App\Modules\Sdk\Enterprise\Contracts\SchedulerPort;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\ScheduledTaskRequest;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowAutomationRule;
use App\Modules\Sdk\Workflow\Automation\Enums\WorkflowAutomationStatus;
use App\Modules\Sdk\Workflow\Automation\Enums\WorkflowTriggerSource;
use App\Modules\Sdk\Workflow\Automation\Exceptions\WorkflowAutomationException;

class WorkflowScheduledTriggerService
{
    public function __construct(
        private readonly SchedulerPort $schedulerPort,
        private readonly WorkflowTriggerService $triggerService,
    ) {
    }

    public function syncScheduleTask(WorkflowAutomationRuleModel $rule): void
    {
        if ($rule->trigger_type !== 'schedule') {
            return;
        }

        if ($rule->status !== WorkflowAutomationStatus::Active) {
            $this->pauseScheduleTask($rule);

            return;
        }

        $config = is_array($rule->trigger_config) ? $rule->trigger_config : [];
        $cronExpression = (string) ($config['cron_expression'] ?? '* * * * *');
        $timezone = (string) ($config['timezone'] ?? 'UTC');
        $existingPublicId = is_array($rule->metadata) ? ($rule->metadata['scheduled_task_public_id'] ?? null) : null;

        $scope = new EnterpriseScope(
            organizationPublicId: $rule->organization->public_id,
            workspacePublicId: $rule->workspace?->public_id,
            moduleKey: $rule->workflowDefinition->module_key ?? 'workflow',
        );

        if (is_string($existingPublicId) && $existingPublicId !== '') {
            $existing = $this->schedulerPort->find($scope, $existingPublicId);

            if ($existing !== null) {
                $this->schedulerPort->resume($scope, $existingPublicId);

                return;
            }
        }

        $reference = $this->schedulerPort->create(new ScheduledTaskRequest(
            scope: $scope,
            taskType: 'workflow.automation.trigger',
            displayName: sprintf('Workflow automation: %s', $rule->workflowDefinition->name ?? $rule->public_id),
            description: 'Scheduled workflow automation trigger',
            cronExpression: $cronExpression,
            runAt: null,
            timezone: $timezone,
            payload: ['rule_public_id' => $rule->public_id],
            entityReference: new EntityReference(
                type: 'workflow_automation_rule',
                publicId: $rule->public_id,
                moduleKey: 'workflow',
                label: $rule->workflowDefinition->name ?? $rule->trigger_type,
            ),
            enabled: true,
            createdMembershipPublicId: $rule->created_by_membership_id !== null
                ? (string) \App\Models\OrganizationMembership::query()->where('id', $rule->created_by_membership_id)->value('public_id')
                : null,
        ));

        $metadata = is_array($rule->metadata) ? $rule->metadata : [];
        $metadata['scheduled_task_public_id'] = $reference->publicId;
        $rule->update(['metadata' => $metadata]);
    }

    public function pauseScheduleTask(WorkflowAutomationRuleModel $rule): void
    {
        $taskPublicId = $this->scheduledTaskPublicId($rule);

        if ($taskPublicId === null) {
            return;
        }

        $scope = $this->scopeForRule($rule);

        try {
            $this->schedulerPort->pause($scope, $taskPublicId);
        } catch (\Throwable) {
        }
    }

    public function cancelScheduleTask(WorkflowAutomationRuleModel $rule): void
    {
        $taskPublicId = $this->scheduledTaskPublicId($rule);

        if ($taskPublicId === null) {
            return;
        }

        $scope = $this->scopeForRule($rule);

        try {
            $this->schedulerPort->cancel($scope, $taskPublicId);
        } catch (\Throwable) {
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function executeFromJob(PlatformJob $job): array
    {
        $payload = is_array($job->payload) ? $job->payload : [];
        $rulePublicId = (string) ($payload['rule_public_id'] ?? '');

        if ($rulePublicId === '') {
            throw new WorkflowAutomationException('Scheduled automation job is missing rule_public_id.');
        }

        $ruleModel = WorkflowAutomationRuleModel::query()
            ->with(['workflowDefinition', 'organization', 'workspace'])
            ->where('public_id', $rulePublicId)
            ->firstOrFail();

        if ($ruleModel->status !== WorkflowAutomationStatus::Active) {
            return ['status' => 'skipped', 'message' => 'Automation rule is not active.'];
        }

        $rule = new WorkflowAutomationRule(
            publicId: $ruleModel->public_id,
            triggerType: $ruleModel->trigger_type,
            status: $ruleModel->status->value,
            workflowDefinitionPublicId: $ruleModel->workflowDefinition->public_id,
            workflowDefinitionName: $ruleModel->workflowDefinition->name,
            triggerConfig: $ruleModel->trigger_config ?? [],
            metadata: $ruleModel->metadata ?? [],
        );

        $result = $this->triggerService->executeRule(
            $rule,
            WorkflowTriggerSource::Schedule->value,
            payload: $payload,
        );

        return [
            'status' => $result->status,
            'workflow_instance_public_id' => $result->workflowInstancePublicId,
        ];
    }

    private function scheduledTaskPublicId(WorkflowAutomationRuleModel $rule): ?string
    {
        $metadata = is_array($rule->metadata) ? $rule->metadata : [];
        $publicId = $metadata['scheduled_task_public_id'] ?? null;

        return is_string($publicId) && $publicId !== '' ? $publicId : null;
    }

    private function scopeForRule(WorkflowAutomationRuleModel $rule): EnterpriseScope
    {
        return new EnterpriseScope(
            organizationPublicId: $rule->organization->public_id,
            workspacePublicId: $rule->workspace?->public_id,
            moduleKey: $rule->workflowDefinition->module_key ?? 'workflow',
        );
    }
}
