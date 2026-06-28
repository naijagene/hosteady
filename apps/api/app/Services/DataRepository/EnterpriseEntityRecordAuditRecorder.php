<?php

namespace App\Services\DataRepository;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Modules\Sdk\DataRepository\Data\EntityRecord;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class EnterpriseEntityRecordAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordCreated(EntityRecord $record): void
    {
        $this->record($record, AuditAction::DataRecordCreated, 'Entity record created');
    }

    public function recordUpdated(EntityRecord $record): void
    {
        $this->record($record, AuditAction::DataRecordUpdated, 'Entity record updated');
    }

    public function recordDeleted(EntityRecord $record): void
    {
        $this->record($record, AuditAction::DataRecordDeleted, 'Entity record deleted');
    }

    public function recordRestored(EntityRecord $record): void
    {
        $this->record($record, AuditAction::DataRecordRestored, 'Entity record restored');
    }

    public function recordVersioned(EntityRecord $record): void
    {
        $this->record($record, AuditAction::DataRecordVersioned, 'Entity record versioned');
    }

    /**
     * @param  array<string, mixed>  $link
     */
    public function recordLinked(array $link): void
    {
        $this->recordReference($link, AuditAction::DataRecordLinked, 'Entity record linked');
    }

    /**
     * @param  array<string, mixed>  $link
     */
    public function recordUnlinked(array $link): void
    {
        $this->recordReference($link, AuditAction::DataRecordUnlinked, 'Entity record unlinked');
    }

    public function recordActivityLogged(string $moduleKey, string $entityKey, string $action, ?string $recordPublicId = null): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::DataRecordActivityLogged,
                summary: 'Entity record activity logged',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace?->id,
                entityType: AuditEntityType::EnterpriseEntityRecord,
                entityPublicId: $recordPublicId ?? sprintf('%s.%s', $moduleKey, $entityKey),
                entityLabel: sprintf('%s.%s', $moduleKey, $entityKey),
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $moduleKey,
                    'entity_key' => $entityKey,
                    'action' => $action,
                ],
            ));
        } catch (\Throwable) {
        }
    }

    public function recordQueried(string $moduleKey, string $entityKey, int $total): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::DataRecordQueried,
                summary: 'Entity records queried',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace?->id,
                entityType: AuditEntityType::EnterpriseEntityRecord,
                entityPublicId: sprintf('%s.%s', $moduleKey, $entityKey),
                entityLabel: sprintf('%s.%s', $moduleKey, $entityKey),
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $moduleKey,
                    'entity_key' => $entityKey,
                    'total' => $total,
                ],
            ));
        } catch (\Throwable) {
        }
    }

    private function record(EntityRecord $record, AuditAction $action, string $summary): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id ?? $record->organizationId,
                workspaceId: $context?->workspace?->id ?? $record->workspaceId,
                entityType: AuditEntityType::EnterpriseEntityRecord,
                entityPublicId: $record->publicId,
                entityLabel: sprintf('%s.%s', $record->moduleKey, $record->entityKey),
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $record->moduleKey,
                    'entity_key' => $record->entityKey,
                    'version' => $record->version,
                ],
            ));
        } catch (\Throwable) {
        }
    }

    /**
     * @param  array<string, mixed>  $reference
     */
    private function recordReference(array $reference, AuditAction $action, string $summary): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace?->id,
                entityType: AuditEntityType::EnterpriseEntityRecord,
                entityPublicId: (string) ($reference['public_id'] ?? ''),
                entityLabel: (string) ($reference['relationship_key'] ?? 'link'),
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: $reference,
            ));
        } catch (\Throwable) {
        }
    }
}
