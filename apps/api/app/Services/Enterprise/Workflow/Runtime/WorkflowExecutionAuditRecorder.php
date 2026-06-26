<?php

namespace App\Services\Enterprise\Workflow\Runtime;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\WorkflowInstance;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class WorkflowExecutionAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordStarted(WorkflowInstance $instance): void
    {
        $this->record($instance, AuditAction::WorkflowExecutionStarted, 'Workflow execution started');
    }

    public function recordCompleted(WorkflowInstance $instance): void
    {
        $this->record($instance, AuditAction::WorkflowExecutionCompleted, 'Workflow execution completed');
    }

    public function recordFailed(WorkflowInstance $instance): void
    {
        $this->record($instance, AuditAction::WorkflowExecutionFailed, 'Workflow execution failed', AuditSeverity::Warning);
    }

    public function recordCancelled(WorkflowInstance $instance): void
    {
        $this->record($instance, AuditAction::WorkflowExecutionCancelled, 'Workflow execution cancelled');
    }

    public function recordResumed(WorkflowInstance $instance): void
    {
        $this->record($instance, AuditAction::WorkflowExecutionResumed, 'Workflow execution resumed');
    }

    public function recordNodeExecuted(WorkflowInstance $instance, string $nodeId, string $nodeType): void
    {
        $this->record(
            $instance,
            AuditAction::WorkflowExecutionNodeExecuted,
            sprintf('Workflow node [%s:%s] executed', $nodeId, $nodeType),
            AuditSeverity::Info,
            ['node_id' => $nodeId, 'node_type' => $nodeType],
        );
    }

    public function recordConditionEvaluated(WorkflowInstance $instance, string $nodeId, ?string $condition, ?string $targetNodeId): void
    {
        $this->record(
            $instance,
            AuditAction::WorkflowExecutionCondition,
            sprintf('Workflow condition evaluated at node [%s]', $nodeId),
            AuditSeverity::Info,
            ['condition' => $condition, 'target_node_id' => $targetNodeId],
        );
    }

    public function recordVariableResolved(string $variableKey, mixed $value): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::WorkflowExecutionVariableResolved,
                summary: sprintf('Workflow variable [%s] resolved', $variableKey),
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::WorkflowInstance,
                entityPublicId: $context?->membershipPublicId ?? 'runtime',
                entityLabel: $variableKey,
                metadata: ['variable_key' => $variableKey, 'resolved' => $value !== null],
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                retentionClass: AuditRetentionClass::Ephemeral,
                severity: AuditSeverity::Info,
            ));
        } catch (\Throwable) {
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function record(
        WorkflowInstance $instance,
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
                organizationId: $instance->organization_id,
                workspaceId: $instance->workspace_id,
                entityType: AuditEntityType::WorkflowInstance,
                entityPublicId: $instance->public_id,
                entityLabel: $instance->definition?->name ?? $instance->public_id,
                metadata: array_merge(['status' => $instance->status->value], $metadata),
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                retentionClass: AuditRetentionClass::Ephemeral,
                severity: $severity,
            ));
        } catch (\Throwable) {
        }
    }
}
