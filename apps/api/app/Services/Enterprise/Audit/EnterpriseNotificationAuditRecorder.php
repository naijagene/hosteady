<?php

namespace App\Services\Enterprise\Audit;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\PlatformNotification;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class EnterpriseNotificationAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordSent(PlatformNotification $notification): void
    {
        $this->record($notification, AuditAction::NotificationSent, sprintf('Notification %s sent', $notification->type));
    }

    public function recordRead(PlatformNotification $notification, TenantContext $context): void
    {
        $this->record($notification, AuditAction::NotificationRead, sprintf('Notification %s read', $notification->type), $context);
    }

    public function recordPreferenceUpdated(TenantContext $context, string $type, string $channel, bool $enabled): void
    {
        try {
            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::NotificationPreferenceUpdated,
                summary: sprintf('Notification preference updated for %s/%s', $type, $channel),
                scope: AuditScope::Organization,
                organizationId: $context->organization->id,
                workspaceId: $context->workspace->id,
                entityType: AuditEntityType::Application,
                entityPublicId: $context->membershipPublicId,
                entityLabel: $type,
                metadata: [
                    'type' => $type,
                    'channel' => $channel,
                    'enabled' => $enabled,
                ],
                actorType: AuditActorType::User,
                actorUserId: $context->user->id,
                actorMembershipId: $context->membership->id,
                retentionClass: AuditRetentionClass::Ephemeral,
                severity: AuditSeverity::Info,
            ));
        } catch (\Throwable) {
        }
    }

    private function record(
        PlatformNotification $notification,
        AuditAction $action,
        string $summary,
        ?TenantContext $context = null,
    ): void {
        try {
            $context ??= app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $notification->organization_id,
                workspaceId: $notification->workspace_id,
                entityType: AuditEntityType::Application,
                entityPublicId: $notification->public_id,
                entityLabel: $notification->type,
                metadata: [
                    'type' => $notification->type,
                    'module_key' => $notification->module_key,
                    'channel' => $notification->channel,
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
