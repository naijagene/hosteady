<?php

namespace App\Services\Enterprise\Workflow\Automation;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\WorkflowAutomationRule;
use App\Models\WorkflowTimer;
use App\Models\WorkflowTriggerExecution;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class WorkflowAutomationAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordRuleCreated(WorkflowAutomationRule $rule): void
    {
        $this->recordRule($rule, AuditAction::WorkflowAutomationRuleCreated, 'Workflow automation rule created');
    }

    public function recordRuleEnabled(WorkflowAutomationRule $rule): void
    {
        $this->recordRule($rule, AuditAction::WorkflowAutomationRuleEnabled, 'Workflow automation rule enabled');
    }

    public function recordRuleDisabled(WorkflowAutomationRule $rule): void
    {
        $this->recordRule($rule, AuditAction::WorkflowAutomationRuleDisabled, 'Workflow automation rule disabled');
    }

    public function recordRuleDeleted(WorkflowAutomationRule $rule): void
    {
        $this->recordRule($rule, AuditAction::WorkflowAutomationRuleDeleted, 'Workflow automation rule deleted');
    }

    public function recordTriggerExecuted(WorkflowTriggerExecution $execution): void
    {
        $this->recordExecution($execution, AuditAction::WorkflowTriggerExecuted, 'Workflow trigger executed');
    }

    public function recordTriggerFailed(WorkflowTriggerExecution $execution): void
    {
        $this->recordExecution(
            $execution,
            AuditAction::WorkflowTriggerFailed,
            'Workflow trigger failed',
            AuditSeverity::Warning,
        );
    }

    public function recordTimerCreated(WorkflowTimer $timer): void
    {
        $this->recordTimer($timer, AuditAction::WorkflowTimerCreated, 'Workflow timer created');
    }

    public function recordTimerExecuted(WorkflowTimer $timer): void
    {
        $this->recordTimer($timer, AuditAction::WorkflowTimerExecuted, 'Workflow timer executed');
    }

    public function recordTimerFailed(WorkflowTimer $timer, ?string $message = null): void
    {
        $this->recordTimer(
            $timer,
            AuditAction::WorkflowTimerFailed,
            'Workflow timer failed',
            AuditSeverity::Warning,
            $message !== null ? ['error_message' => $message] : [],
        );
    }

    public function recordTimerCancelled(WorkflowTimer $timer): void
    {
        $this->recordTimer($timer, AuditAction::WorkflowTimerCancelled, 'Workflow timer cancelled');
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordRule(
        WorkflowAutomationRule $rule,
        AuditAction $action,
        string $summary,
        AuditSeverity $severity = AuditSeverity::Info,
        array $metadata = [],
    ): void {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $rule->organization_id,
                workspaceId: $rule->workspace_id,
                entityType: AuditEntityType::WorkflowAutomationRule,
                entityPublicId: $rule->public_id,
                entityLabel: $rule->workflowDefinition?->name ?? $rule->trigger_type,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: $severity,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: array_merge([
                    'trigger_type' => $rule->trigger_type,
                    'workflow_definition_public_id' => $rule->workflowDefinition?->public_id,
                ], $metadata),
            ));
        } catch (\Throwable) {
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordExecution(
        WorkflowTriggerExecution $execution,
        AuditAction $action,
        string $summary,
        AuditSeverity $severity = AuditSeverity::Info,
        array $metadata = [],
    ): void {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $execution->organization_id,
                workspaceId: $execution->workspace_id,
                entityType: AuditEntityType::WorkflowTriggerExecution,
                entityPublicId: $execution->public_id,
                entityLabel: $execution->automationRule?->trigger_type ?? 'trigger',
                actorType: AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: $severity,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: array_merge([
                    'trigger_source' => $execution->trigger_source->value,
                    'event_name' => $execution->event_name,
                    'rule_public_id' => $execution->automationRule?->public_id,
                ], $metadata),
            ));
        } catch (\Throwable) {
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordTimer(
        WorkflowTimer $timer,
        AuditAction $action,
        string $summary,
        AuditSeverity $severity = AuditSeverity::Info,
        array $metadata = [],
    ): void {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $timer->organization_id,
                workspaceId: $timer->workspace_id,
                entityType: AuditEntityType::WorkflowTimer,
                entityPublicId: $timer->public_id,
                entityLabel: $timer->node_id,
                actorType: AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: $severity,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: array_merge([
                    'node_id' => $timer->node_id,
                    'timer_type' => $timer->timer_type,
                    'workflow_instance_public_id' => $timer->workflowInstance?->public_id,
                ], $metadata),
            ));
        } catch (\Throwable) {
        }
    }
}
