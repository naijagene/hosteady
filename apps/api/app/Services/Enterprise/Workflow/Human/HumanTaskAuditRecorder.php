<?php

namespace App\Services\Enterprise\Workflow\Human;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\WorkflowHumanTask;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class HumanTaskAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordCreated(WorkflowHumanTask $task): void
    {
        $this->record($task, AuditAction::TaskCreated, 'Human task created');
    }

    public function recordAssigned(WorkflowHumanTask $task): void
    {
        $this->record($task, AuditAction::TaskAssigned, 'Human task assigned');
    }

    public function recordOpened(WorkflowHumanTask $task): void
    {
        $this->record($task, AuditAction::TaskOpened, 'Human task opened');
    }

    public function recordCompleted(WorkflowHumanTask $task): void
    {
        $this->record($task, AuditAction::TaskCompleted, 'Human task completed');
    }

    public function recordCancelled(WorkflowHumanTask $task): void
    {
        $this->record($task, AuditAction::TaskCancelled, 'Human task cancelled');
    }

    public function recordCommented(WorkflowHumanTask $task, string $commentPublicId): void
    {
        $this->record($task, AuditAction::TaskCommented, 'Human task comment added', metadata: ['comment_public_id' => $commentPublicId]);
    }

    public function recordEscalated(WorkflowHumanTask $task, string $escalationPublicId): void
    {
        $this->record($task, AuditAction::TaskEscalated, 'Human task escalated', metadata: ['escalation_public_id' => $escalationPublicId]);
    }

    public function recordApprovalRequested(WorkflowHumanTask $task): void
    {
        $this->record($task, AuditAction::ApprovalRequested, 'Approval requested');
    }

    public function recordApprovalApproved(WorkflowHumanTask $task): void
    {
        $this->record($task, AuditAction::ApprovalApproved, 'Approval approved');
    }

    public function recordApprovalRejected(WorkflowHumanTask $task): void
    {
        $this->record($task, AuditAction::ApprovalRejected, 'Approval rejected', AuditSeverity::Warning);
    }

    public function recordApprovalCompleted(WorkflowHumanTask $task): void
    {
        $this->record($task, AuditAction::ApprovalCompleted, 'Approval completed');
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function record(
        WorkflowHumanTask $task,
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
                organizationId: $task->organization_id,
                workspaceId: $task->workspace_id,
                entityType: AuditEntityType::WorkflowHumanTask,
                entityPublicId: $task->public_id,
                entityLabel: $task->title,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: $severity,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: array_merge([
                    'task_type' => $task->task_type,
                    'workflow_instance_id' => $task->workflowInstance?->public_id,
                ], $metadata),
            ));
        } catch (\Throwable) {
        }
    }
}
