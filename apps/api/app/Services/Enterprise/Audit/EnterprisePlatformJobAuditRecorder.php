<?php

namespace App\Services\Enterprise\Audit;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\PlatformJob;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class EnterprisePlatformJobAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordDispatched(PlatformJob $job): void
    {
        $this->record($job, AuditAction::PlatformJobDispatched, sprintf('Job %s dispatched', $job->job_type));
    }

    public function recordStarted(PlatformJob $job): void
    {
        $this->record($job, AuditAction::PlatformJobStarted, sprintf('Job %s started', $job->job_type));
    }

    public function recordCompleted(PlatformJob $job): void
    {
        $this->record($job, AuditAction::PlatformJobCompleted, sprintf('Job %s completed', $job->job_type));
    }

    public function recordFailed(PlatformJob $job): void
    {
        $this->record(
            $job,
            AuditAction::PlatformJobFailed,
            sprintf('Job %s failed', $job->job_type),
            AuditSeverity::Warning,
        );
    }

    public function recordCancelled(PlatformJob $job): void
    {
        $this->record($job, AuditAction::PlatformJobCancelled, sprintf('Job %s cancelled', $job->job_type));
    }

    private function record(
        PlatformJob $job,
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
                organizationId: $job->organization_id,
                workspaceId: $job->workspace_id,
                entityType: AuditEntityType::PlatformJob,
                entityPublicId: $job->public_id,
                entityLabel: $job->display_name ?? $job->job_type,
                metadata: [
                    'job_type' => $job->job_type,
                    'module_key' => $job->module_key,
                    'status' => $job->status->value,
                    'attempts' => $job->attempts,
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
