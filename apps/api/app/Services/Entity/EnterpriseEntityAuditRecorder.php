<?php

namespace App\Services\Entity;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\EntityComment;
use App\Models\EntityDefinition;
use App\Models\EntityRelationship;
use App\Models\EntityTag;
use App\Modules\Sdk\Entity\Data\EntityLifecycleEvent;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class EnterpriseEntityAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordDefinitionRegistered(EntityDefinition $definition): void
    {
        $this->recordDefinition($definition, AuditAction::EntityDefinitionRegistered, 'Entity definition registered');
    }

    public function recordDefinitionUpdated(EntityDefinition $definition): void
    {
        $this->recordDefinition($definition, AuditAction::EntityDefinitionUpdated, 'Entity definition updated');
    }

    public function recordValidated(\App\Modules\Sdk\Entity\Data\EntityDefinition $definition): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::ModuleValidationExecuted,
                summary: 'Entity definition validated',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::EntityDefinition,
                entityPublicId: $definition->publicId,
                entityLabel: $definition->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $definition->moduleKey,
                    'entity_key' => $definition->entityKey,
                ],
            ));
        } catch (\Throwable) {
        }
    }

    public function recordRelationshipRegistered(EntityRelationship $relationship): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::EntityRelationshipRegistered,
                summary: 'Entity relationship registered',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::EntityRelationship,
                entityPublicId: $relationship->public_id,
                entityLabel: $relationship->relationship_key,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'relationship_key' => $relationship->relationship_key,
                    'source_module_key' => $relationship->source_module_key,
                    'source_entity_key' => $relationship->source_entity_key,
                ],
            ));
        } catch (\Throwable) {
        }
    }

    public function recordActivityLogged(string $moduleKey, string $entityKey, string $action, ?string $entityPublicId = null): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::EntityActivityLogged,
                summary: 'Entity activity logged',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::EntityDefinition,
                entityPublicId: $entityPublicId ?? sprintf('%s.%s', $moduleKey, $entityKey),
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

    public function recordCommentCreated(EntityComment $comment): void
    {
        $this->recordComment($comment, AuditAction::EntityCommentCreated, 'Entity comment created');
    }

    public function recordCommentDeleted(EntityComment $comment): void
    {
        $this->recordComment($comment, AuditAction::EntityCommentDeleted, 'Entity comment deleted');
    }

    public function recordTagCreated(EntityTag $tag): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::EntityTagCreated,
                summary: 'Entity tag created',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id ?? $tag->organization_id,
                workspaceId: $context?->workspace->id ?? $tag->workspace_id,
                entityType: AuditEntityType::EntityTag,
                entityPublicId: $tag->public_id,
                entityLabel: $tag->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: ['tag_key' => $tag->tag_key],
            ));
        } catch (\Throwable) {
        }
    }

    public function recordTagAttached(string $moduleKey, string $entityKey, string $entityPublicId, EntityTag $tag): void
    {
        $this->recordTagEvent(
            AuditAction::EntityTagAttached,
            'Entity tag attached',
            $moduleKey,
            $entityKey,
            $entityPublicId,
            $tag,
        );
    }

    public function recordTagDetached(string $moduleKey, string $entityKey, string $entityPublicId, EntityTag $tag): void
    {
        $this->recordTagEvent(
            AuditAction::EntityTagDetached,
            'Entity tag detached',
            $moduleKey,
            $entityKey,
            $entityPublicId,
            $tag,
        );
    }

    public function recordLifecycleEvent(EntityLifecycleEvent $event): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::EntityLifecycleEvent,
                summary: 'Entity lifecycle event dispatched',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::EntityDefinition,
                entityPublicId: $event->entityPublicId ?? sprintf('%s.%s', $event->moduleKey, $event->entityKey),
                entityLabel: sprintf('%s.%s', $event->moduleKey, $event->entityKey),
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'event_type' => $event->eventType,
                    'module_key' => $event->moduleKey,
                    'entity_key' => $event->entityKey,
                ],
            ));
        } catch (\Throwable) {
        }
    }

    private function recordDefinition(EntityDefinition $definition, AuditAction $action, string $summary): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::EntityDefinition,
                entityPublicId: $definition->public_id,
                entityLabel: $definition->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $definition->module_key,
                    'entity_key' => $definition->entity_key,
                ],
            ));
        } catch (\Throwable) {
        }
    }

    private function recordComment(EntityComment $comment, AuditAction $action, string $summary): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id ?? $comment->organization_id,
                workspaceId: $context?->workspace->id ?? $comment->workspace_id,
                entityType: AuditEntityType::EntityComment,
                entityPublicId: $comment->public_id,
                entityLabel: sprintf('%s.%s', $comment->module_key, $comment->entity_key),
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $comment->module_key,
                    'entity_key' => $comment->entity_key,
                    'entity_public_id' => $comment->entity_public_id,
                ],
            ));
        } catch (\Throwable) {
        }
    }

    private function recordTagEvent(
        AuditAction $action,
        string $summary,
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
        EntityTag $tag,
    ): void {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id ?? $tag->organization_id,
                workspaceId: $context?->workspace->id ?? $tag->workspace_id,
                entityType: AuditEntityType::EntityTag,
                entityPublicId: $tag->public_id,
                entityLabel: $tag->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $moduleKey,
                    'entity_key' => $entityKey,
                    'entity_public_id' => $entityPublicId,
                    'tag_key' => $tag->tag_key,
                ],
            ));
        } catch (\Throwable) {
        }
    }
}
