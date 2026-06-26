<?php

namespace App\Services\Enterprise\Audit;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\ScheduledTask;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class EnterpriseSchedulerAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordCreated(ScheduledTask $task): void
    {
        $this->record($task, AuditAction::SchedulerTaskCreated, sprintf('Scheduled task %s created', $task->task_type));
    }

    public function recordUpdated(ScheduledTask $task): void
    {
        $this->record($task, AuditAction::SchedulerTaskUpdated, sprintf('Scheduled task %s updated', $task->task_type));
    }

    public function recordPaused(ScheduledTask $task): void
    {
        $this->record($task, AuditAction::SchedulerTaskPaused, sprintf('Scheduled task %s paused', $task->task_type));
    }

    public function recordResumed(ScheduledTask $task): void
    {
        $this->record($task, AuditAction::SchedulerTaskResumed, sprintf('Scheduled task %s resumed', $task->task_type));
    }

    public function recordCancelled(ScheduledTask $task): void
    {
        $this->record($task, AuditAction::SchedulerTaskCancelled, sprintf('Scheduled task %s cancelled', $task->task_type));
    }

    public function recordExecuted(ScheduledTask $task): void
    {
        $this->record($task, AuditAction::SchedulerTaskExecuted, sprintf('Scheduled task %s executed', $task->task_type));
    }

    public function recordFailed(ScheduledTask $task, ?string $message = null): void
    {
        $this->record(
            $task,
            AuditAction::SchedulerTaskFailed,
            $message ?? sprintf('Scheduled task %s failed', $task->task_type),
            AuditSeverity::Warning,
        );
    }

    private function record(
        ScheduledTask $task,
        AuditAction $action,
        string $summary,
        AuditSeverity $severity = AuditSeverity::Info,
    ): void {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $task->organization_id,
                workspaceId: $task->workspace_id,
                entityType: AuditEntityType::ScheduledTask,
                entityPublicId: $task->public_id,
                entityLabel: $task->display_name,
                metadata: [
                    'task_type' => $task->task_type,
                    'module_key' => $task->module_key,
                    'status' => $task->status->value,
                ],
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
