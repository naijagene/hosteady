<?php

namespace App\Services\Enterprise\Workflow\Automation;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Models\WorkflowAutomationRule as WorkflowAutomationRuleModel;
use App\Models\WorkflowInstance;
use App\Models\WorkflowTriggerExecution;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\PlatformEventData;
use App\Modules\Sdk\Workflow\Automation\Contracts\WorkflowTriggerHandler;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowAutomationResult;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowAutomationRule;
use App\Modules\Sdk\Workflow\Automation\Enums\WorkflowTriggerExecutionStatus;
use App\Modules\Sdk\Workflow\Automation\Enums\WorkflowTriggerSource;
use App\Modules\Sdk\Workflow\Automation\Exceptions\WorkflowTriggerException;
use App\Modules\Sdk\Workflow\Runtime\Contracts\WorkflowRuntimePort;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionContext;
use Illuminate\Support\Facades\DB;

class WorkflowTriggerService implements WorkflowTriggerHandler
{
    public function __construct(
        private readonly WorkflowRuntimePort $runtimePort,
        private readonly WorkflowAutomationAuditRecorder $auditRecorder,
    ) {
    }

    public function executeRule(
        WorkflowAutomationRule $rule,
        string $triggerSource,
        ?PlatformEventData $event = null,
        ?array $payload = null,
    ): WorkflowAutomationResult {
        $model = WorkflowAutomationRuleModel::query()
            ->with(['workflowDefinition', 'organization', 'workspace'])
            ->where('public_id', $rule->publicId)
            ->firstOrFail();

        $source = WorkflowTriggerSource::tryFrom($triggerSource) ?? WorkflowTriggerSource::Manual;

        $execution = WorkflowTriggerExecution::query()->create([
            'organization_id' => $model->organization_id,
            'workspace_id' => $model->workspace_id,
            'workflow_automation_rule_id' => $model->id,
            'trigger_source' => $source,
            'status' => WorkflowTriggerExecutionStatus::Running,
            'event_name' => $event?->eventName,
            'metadata' => [
                'payload' => $payload ?? $event?->payload ?? [],
            ],
            'executed_at' => now(),
        ]);

        try {
            $scope = new EnterpriseScope(
                organizationPublicId: $model->organization->public_id,
                workspacePublicId: $model->workspace?->public_id,
                moduleKey: $model->workflowDefinition->module_key,
            );

            $context = new WorkflowExecutionContext(
                organizationPublicId: $scope->organizationPublicId,
                workspacePublicId: $scope->workspacePublicId,
                moduleKey: $scope->moduleKey,
                metadata: [
                    'automation_rule_public_id' => $model->public_id,
                    'trigger_source' => $source->value,
                ],
            );

            [$userId, $membershipId] = $this->resolveActors($model);

            $result = $this->runtimePort->execute(
                $scope,
                $model->workflowDefinition->public_id,
                $context,
                $payload ?? $event?->payload,
                $userId,
                $membershipId,
            );

            $instance = WorkflowInstance::query()
                ->where('public_id', $result->instance->publicId)
                ->first();

            $execution->update([
                'status' => WorkflowTriggerExecutionStatus::Succeeded,
                'workflow_instance_id' => $instance?->id,
            ]);

            $this->auditRecorder->recordTriggerExecuted($execution->fresh(['automationRule']));

            return new WorkflowAutomationResult(
                status: 'succeeded',
                workflowInstancePublicId: $result->instance->publicId,
                metadata: ['execution_public_id' => $execution->public_id],
            );
        } catch (\Throwable $exception) {
            $execution->update([
                'status' => WorkflowTriggerExecutionStatus::Failed,
                'error_message' => $exception->getMessage(),
            ]);

            $this->auditRecorder->recordTriggerFailed($execution->fresh(['automationRule']));

            throw new WorkflowTriggerException($exception->getMessage(), previous: $exception);
        }
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveActors(WorkflowAutomationRuleModel $rule): array
    {
        if ($rule->created_by_user_id !== null) {
            return [$rule->created_by_user_id, $rule->created_by_membership_id];
        }

        $membership = OrganizationMembership::query()
            ->where('organization_id', $rule->organization_id)
            ->where('status', \App\Enums\MembershipStatus::Active)
            ->orderBy('created_at')
            ->first();

        if ($membership === null) {
            return [null, null];
        }

        return [$membership->user_id, $membership->id];
    }
}
