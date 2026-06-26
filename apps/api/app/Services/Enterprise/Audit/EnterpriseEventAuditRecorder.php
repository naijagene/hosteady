<?php

namespace App\Services\Enterprise\Audit;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\PlatformEvent;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class EnterpriseEventAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordDispatched(PlatformEvent $event): void
    {
        $this->record($event, AuditAction::PlatformEventDispatched, sprintf('Platform event %s dispatched', $event->event_name));
    }

    public function recordProcessed(PlatformEvent $event): void
    {
        $this->record($event, AuditAction::PlatformEventProcessed, sprintf('Platform event %s processed', $event->event_name));
    }

    public function recordFailed(PlatformEvent $event, string $message): void
    {
        $this->record($event, AuditAction::PlatformEventFailed, sprintf('Platform event %s failed: %s', $event->event_name, $message), AuditSeverity::Warning);
    }

    private function record(
        PlatformEvent $event,
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
                organizationId: $event->organization_id,
                workspaceId: $event->workspace_id,
                entityType: AuditEntityType::Application,
                entityPublicId: $event->public_id,
                entityLabel: $event->event_name,
                metadata: [
                    'event_name' => $event->event_name,
                    'module_key' => $event->module_key,
                    'correlation_id' => $event->correlation_id,
                ],
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                retentionClass: AuditRetentionClass::Ephemeral,
                severity: $severity,
            ));
        } catch (\Throwable) {
            // Audit failures must never stop enterprise services.
        }
    }
}
