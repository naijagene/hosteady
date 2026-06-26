<?php

namespace App\Services\Enterprise\Workflow\Automation;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Automation\Contracts\WorkflowAutomationPort;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowAutomationRule;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowAutomationStatistics;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowTimerReference;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowTriggerReference;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;

class WorkflowAutomationService
{
    public function __construct(
        private readonly WorkflowAutomationPort $automationPort,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
        private readonly WorkflowAutomationIntegrations $integrations,
    ) {
    }

    /**
     * @return list<WorkflowAutomationRule>
     */
    public function listRules(TenantContext $context, ?string $status = null): array
    {
        $this->runtimeBridge->requireCapability($context, 'automation');
        $this->assertReadPermission($context);

        return $this->automationPort->listRules($this->scope($context), $status);
    }

    public function showRule(TenantContext $context, string $publicId): WorkflowAutomationRule
    {
        $this->runtimeBridge->requireCapability($context, 'automation');
        $this->assertReadPermission($context);

        return $this->automationPort->getRule($this->scope($context), $publicId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRule(TenantContext $context, array $data): WorkflowAutomationRule
    {
        $this->runtimeBridge->requireCapability($context, 'automation');
        $this->assertManagePermission($context);

        $rule = $this->automationPort->createRule(
            $this->scope($context),
            $data,
            $context->user->id,
            $context->membership->id,
        );

        $this->integrateRule($context, $rule->publicId);

        return $rule;
    }

    public function enableRule(TenantContext $context, string $publicId): WorkflowAutomationRule
    {
        $this->runtimeBridge->requireCapability($context, 'automation');
        $this->assertManagePermission($context);

        $rule = $this->automationPort->enableRule($this->scope($context), $publicId);
        $this->integrateRule($context, $rule->publicId);

        return $rule;
    }

    public function disableRule(TenantContext $context, string $publicId): WorkflowAutomationRule
    {
        $this->runtimeBridge->requireCapability($context, 'automation');
        $this->assertManagePermission($context);

        $rule = $this->automationPort->disableRule($this->scope($context), $publicId);
        $this->integrateRule($context, $rule->publicId);

        return $rule;
    }

    public function deleteRule(TenantContext $context, string $publicId): void
    {
        $this->runtimeBridge->requireCapability($context, 'automation');
        $this->assertManagePermission($context);

        $this->automationPort->deleteRule($this->scope($context), $publicId);
    }

    /**
     * @return list<WorkflowTriggerReference>
     */
    public function listTriggers(TenantContext $context, int $limit = 50): array
    {
        $this->runtimeBridge->requireCapability($context, 'automation');
        $this->assertReadPermission($context);

        return $this->automationPort->listTriggerExecutions($this->scope($context), $limit);
    }

    /**
     * @return list<WorkflowTimerReference>
     */
    public function listTimers(TenantContext $context, ?string $status = null, int $limit = 50): array
    {
        $this->runtimeBridge->requireCapability($context, 'automation');
        $this->assertReadPermission($context);

        return $this->automationPort->listTimers($this->scope($context), $status, $limit);
    }

    public function statistics(TenantContext $context): WorkflowAutomationStatistics
    {
        $this->runtimeBridge->requireCapability($context, 'automation');
        $this->assertReadPermission($context);

        return $this->automationPort->statistics($this->scope($context));
    }

    private function integrateRule(TenantContext $context, string $rulePublicId): void
    {
        $rule = \App\Models\WorkflowAutomationRule::query()
            ->with('workflowDefinition')
            ->where('public_id', $rulePublicId)
            ->first();

        if ($rule === null) {
            return;
        }

        $this->integrations->indexRuleBestEffort($context, $rule);
        $this->integrations->notifyRuleEventBestEffort($context, 'workflow.automation.updated', $rule);
    }

    private function scope(TenantContext $context): EnterpriseScope
    {
        return new EnterpriseScope(
            organizationPublicId: $context->organizationPublicId,
            workspacePublicId: $context->workspacePublicId,
        );
    }

    private function assertReadPermission(TenantContext $context): void
    {
        if (! $this->allows($context, 'workflow.automation.read')) {
            abort(403, 'You are not allowed to read workflow automation.');
        }
    }

    private function assertManagePermission(TenantContext $context): void
    {
        if (! $this->allows($context, 'workflow.automation.manage')) {
            abort(403, 'You are not allowed to manage workflow automation.');
        }
    }

    private function allows(TenantContext $context, string $permission): bool
    {
        return app(\App\Services\Authorization\TenantAuthorizationService::class)
            ->allows($context, $permission);
    }
}
