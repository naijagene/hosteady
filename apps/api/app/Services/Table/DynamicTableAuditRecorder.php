<?php

namespace App\Services\Table;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\TableDefinition as TableDefinitionModel;
use App\Modules\Sdk\Table\Data\TableDefinition;
use App\Modules\Sdk\Table\Data\TableQueryResult;
use App\Modules\Sdk\Table\Data\TableView;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class DynamicTableAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordDefinitionRegistered(TableDefinitionModel $definition): void
    {
        $this->recordDefinition($definition, AuditAction::TableDefinitionRegistered, 'Table definition registered');
    }

    public function recordDefinitionUpdated(TableDefinitionModel $definition): void
    {
        $this->recordDefinition($definition, AuditAction::TableDefinitionUpdated, 'Table definition updated');
    }

    public function recordRendered(TableDefinition $definition): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::TableRendered,
                summary: 'Table rendered',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::TableDefinition,
                entityPublicId: $definition->publicId,
                entityLabel: $definition->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $definition->moduleKey,
                    'table_key' => $definition->tableKey,
                    'entity_key' => $definition->entityKey,
                    'resource' => 'table_definition',
                ],
            ));
        } catch (\Throwable) {
        }
    }

    public function recordQueried(TableDefinition $definition, TableQueryResult $result): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::TableQueried,
                summary: 'Table queried',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::TableDefinition,
                entityPublicId: $definition->publicId,
                entityLabel: $definition->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $definition->moduleKey,
                    'table_key' => $definition->tableKey,
                    'total' => $result->total,
                    'page' => $result->page,
                    'resource' => 'table_query',
                ],
            ));
        } catch (\Throwable) {
        }
    }

    public function recordViewSaved(TableView $view): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::TableViewSaved,
                summary: 'Table view saved',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id ?? $view->organizationId,
                workspaceId: $context?->workspace->id ?? $view->workspaceId,
                entityType: AuditEntityType::TableView,
                entityPublicId: $view->publicId,
                entityLabel: $view->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $view->moduleKey,
                    'table_key' => $view->tableKey,
                    'resource' => 'table_view',
                ],
            ));
        } catch (\Throwable) {
        }
    }

    public function recordViewDeleted(?string $viewPublicId = null): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::TableViewDeleted,
                summary: 'Table view deleted',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::TableView,
                entityPublicId: $viewPublicId,
                entityLabel: 'table_view',
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: ['resource' => 'table_view'],
            ));
        } catch (\Throwable) {
        }
    }

    public function recordActivityLogged(
        string $action,
        ?string $tableDefinitionId = null,
    ): void {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::TableActivityLogged,
                summary: 'Table activity logged',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::TableDefinition,
                entityPublicId: $tableDefinitionId,
                entityLabel: 'table_activity',
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'action' => $action,
                    'table_definition_id' => $tableDefinitionId,
                    'resource' => 'table_activity',
                ],
            ));
        } catch (\Throwable) {
        }
    }

    private function recordDefinition(
        TableDefinitionModel $definition,
        AuditAction $action,
        string $summary,
    ): void {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id ?? $definition->organization_id,
                workspaceId: $context?->workspace->id ?? $definition->workspace_id,
                entityType: AuditEntityType::TableDefinition,
                entityPublicId: $definition->public_id,
                entityLabel: $definition->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $definition->module_key,
                    'table_key' => $definition->table_key,
                    'entity_key' => $definition->entity_key,
                    'resource' => 'table_definition',
                ],
            ));
        } catch (\Throwable) {
        }
    }
}
