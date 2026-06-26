<?php

namespace App\Services\Enterprise\Audit;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\PlatformFile;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class EnterpriseFileAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordUploaded(PlatformFile $file): void
    {
        $this->record($file, AuditAction::FileUploaded, sprintf('File %s uploaded', $file->original_filename));
    }

    public function recordUpdated(PlatformFile $file): void
    {
        $this->record($file, AuditAction::FileUpdated, sprintf('File %s updated', $file->original_filename));
    }

    public function recordDeleted(PlatformFile $file): void
    {
        $this->record($file, AuditAction::FileDeleted, sprintf('File %s deleted', $file->original_filename));
    }

    public function recordDownloaded(PlatformFile $file): void
    {
        $this->record($file, AuditAction::FileDownloaded, sprintf('File %s downloaded', $file->original_filename));
    }

    public function recordAccessDenied(TenantContext $context, string $filePublicId): void
    {
        try {
            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::FileAccessDenied,
                summary: sprintf('File access denied for %s', $filePublicId),
                scope: AuditScope::Organization,
                organizationId: $context->organization->id,
                workspaceId: $context->workspace->id,
                entityType: AuditEntityType::PlatformFile,
                entityPublicId: $filePublicId,
                entityLabel: $filePublicId,
                metadata: [
                    'membership_public_id' => $context->membershipPublicId,
                ],
                actorType: AuditActorType::User,
                actorUserId: $context->user->id,
                actorMembershipId: $context->membership->id,
                retentionClass: AuditRetentionClass::Ephemeral,
                severity: AuditSeverity::Warning,
            ));
        } catch (\Throwable) {
        }
    }

    private function record(PlatformFile $file, AuditAction $action, string $summary): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $file->organization_id,
                workspaceId: $file->workspace_id,
                entityType: AuditEntityType::PlatformFile,
                entityPublicId: $file->public_id,
                entityLabel: $file->display_name ?? $file->original_filename,
                metadata: [
                    'module_key' => $file->module_key,
                    'mime_type' => $file->mime_type,
                    'visibility' => $file->visibility->value,
                    'size_bytes' => $file->size_bytes,
                ],
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                retentionClass: AuditRetentionClass::Ephemeral,
                severity: AuditSeverity::Info,
            ));
        } catch (\Throwable) {
        }
    }
}
