<?php

namespace App\Services\Notification;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Modules\Sdk\Notification\Data\NotificationDelivery;
use App\Modules\Sdk\Notification\Data\NotificationDigest;
use App\Modules\Sdk\Notification\Data\NotificationReference;
use App\Modules\Sdk\Notification\Data\NotificationSchedule;
use App\Modules\Sdk\Notification\Data\NotificationTemplate;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

/**
 * Parent should add these AuditAction enum cases:
 * - NotificationDeleted = 'notification.deleted'
 * - NotificationUnread = 'notification.unread'
 * - NotificationDelivered = 'notification.delivered'
 * - NotificationDeliveryFailed = 'notification.delivery.failed'
 * - NotificationTemplateCreated = 'notification.template.created'
 * - NotificationTemplateUpdated = 'notification.template.updated'
 * - NotificationScheduled = 'notification.scheduled'
 * - NotificationScheduleCancelled = 'notification.schedule.cancelled'
 * - NotificationDigestGenerated = 'notification.digest.generated'
 */
class NotificationAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordSent(NotificationReference $notification): void
    {
        $this->recordNotification($notification, AuditAction::NotificationSent, 'Enterprise notification sent');
    }

    public function recordRead(NotificationReference $notification): void
    {
        $this->recordNotification($notification, AuditAction::NotificationRead, 'Enterprise notification read');
    }

    public function recordUnread(NotificationReference $notification): void
    {
        $this->recordNotification($notification, AuditAction::NotificationRead, 'Enterprise notification marked unread', [
            'enterprise_action' => 'unread',
        ]);
    }

    public function recordDeleted(NotificationReference $notification): void
    {
        $this->recordNotification($notification, AuditAction::NotificationSent, 'Enterprise notification deleted', [
            'enterprise_action' => 'deleted',
        ]);
    }

    public function recordDelivered(NotificationReference $notification, NotificationDelivery $delivery): void
    {
        $this->recordNotification($notification, AuditAction::NotificationSent, 'Enterprise notification delivered', [
            'enterprise_action' => 'delivered',
            'delivery' => $delivery->toArray(),
        ]);
    }

    public function recordDeliveryFailed(NotificationReference $notification, NotificationDelivery $delivery): void
    {
        $this->recordNotification($notification, AuditAction::NotificationSent, 'Enterprise notification delivery failed', [
            'enterprise_action' => 'delivery_failed',
            'delivery' => $delivery->toArray(),
        ]);
    }

    public function recordTemplateCreated(NotificationTemplate $template): void
    {
        $this->recordTemplate($template, AuditAction::NotificationPreferenceUpdated, 'Notification template created', [
            'enterprise_action' => 'template_created',
        ]);
    }

    public function recordTemplateUpdated(NotificationTemplate $template): void
    {
        $this->recordTemplate($template, AuditAction::NotificationPreferenceUpdated, 'Notification template updated', [
            'enterprise_action' => 'template_updated',
        ]);
    }

    public function recordScheduled(NotificationSchedule $schedule): void
    {
        $this->recordSchedule($schedule, AuditAction::NotificationSent, 'Notification schedule created', [
            'enterprise_action' => 'scheduled',
        ]);
    }

    public function recordScheduleCancelled(NotificationSchedule $schedule): void
    {
        $this->recordSchedule($schedule, AuditAction::NotificationSent, 'Notification schedule cancelled', [
            'enterprise_action' => 'schedule_cancelled',
        ]);
    }

    public function recordDigestGenerated(NotificationDigest $digest): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::NotificationSent,
                summary: 'Notification digest generated',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace?->id,
                entityType: AuditEntityType::Application,
                entityPublicId: $digest->publicId,
                entityLabel: $digest->frequency,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: array_merge($digest->toArray(), ['enterprise_action' => 'digest_generated']),
            ));
        } catch (\Throwable) {
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordNotification(
        NotificationReference $notification,
        AuditAction $action,
        string $summary,
        array $metadata = [],
    ): void {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace?->id,
                entityType: AuditEntityType::Application,
                entityPublicId: $notification->publicId,
                entityLabel: $notification->title,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: array_merge([
                    'status' => $notification->status,
                    'scope' => $notification->scope,
                    'priority' => $notification->priority,
                ], $metadata),
            ));
        } catch (\Throwable) {
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordTemplate(NotificationTemplate $template, AuditAction $action, string $summary, array $metadata = []): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace?->id,
                entityType: AuditEntityType::Application,
                entityPublicId: $template->publicId,
                entityLabel: $template->type,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: array_merge($template->toArray(), $metadata),
            ));
        } catch (\Throwable) {
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordSchedule(NotificationSchedule $schedule, AuditAction $action, string $summary, array $metadata = []): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace?->id,
                entityType: AuditEntityType::Application,
                entityPublicId: $schedule->publicId,
                entityLabel: $schedule->title,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: array_merge($schedule->toArray(), $metadata),
            ));
        } catch (\Throwable) {
        }
    }
}
