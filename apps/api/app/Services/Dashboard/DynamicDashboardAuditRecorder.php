<?php

namespace App\Services\Dashboard;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\DashboardDefinition as DashboardDefinitionModel;
use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;
use App\Modules\Sdk\Dashboard\Data\DashboardView;
use App\Modules\Sdk\Dashboard\Data\DashboardWidget;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class DynamicDashboardAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordDefinitionRegistered(DashboardDefinitionModel $definition): void
    {
        $this->recordDefinition($definition, AuditAction::DashboardDefinitionRegistered, 'Dashboard definition registered');
    }

    public function recordDefinitionUpdated(DashboardDefinitionModel $definition): void
    {
        $this->recordDefinition($definition, AuditAction::DashboardDefinitionUpdated, 'Dashboard definition updated');
    }

    public function recordRendered(DashboardDefinition $definition): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::DashboardRendered,
                summary: 'Dashboard rendered',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::DashboardDefinition,
                entityPublicId: $definition->publicId,
                entityLabel: $definition->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $definition->moduleKey,
                    'dashboard_key' => $definition->dashboardKey,
                    'entity_key' => $definition->entityKey,
                    'resource' => 'dashboard_definition',
                ],
            ));
        } catch (\Throwable) {
        }
    }

    public function recordWidgetCreated(DashboardWidget $widget): void
    {
        $this->recordWidget($widget, AuditAction::DashboardWidgetCreated, 'Dashboard widget created');
    }

    public function recordWidgetUpdated(DashboardWidget $widget): void
    {
        $this->recordWidget($widget, AuditAction::DashboardWidgetUpdated, 'Dashboard widget updated');
    }

    public function recordWidgetDeleted(?string $widgetPublicId = null): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::DashboardWidgetDeleted,
                summary: 'Dashboard widget deleted',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::DashboardWidget,
                entityPublicId: $widgetPublicId,
                entityLabel: 'dashboard_widget',
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: ['resource' => 'dashboard_widget'],
            ));
        } catch (\Throwable) {
        }
    }

    public function recordViewCreated(DashboardView $view): void
    {
        $this->recordView($view, AuditAction::DashboardViewCreated, 'Dashboard view created');
    }

    public function recordViewUpdated(DashboardView $view): void
    {
        $this->recordView($view, AuditAction::DashboardViewUpdated, 'Dashboard view updated');
    }

    public function recordViewDeleted(?string $viewPublicId = null): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::DashboardViewDeleted,
                summary: 'Dashboard view deleted',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::DashboardView,
                entityPublicId: $viewPublicId,
                entityLabel: 'dashboard_view',
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: ['resource' => 'dashboard_view'],
            ));
        } catch (\Throwable) {
        }
    }

    public function recordActivityLogged(
        string $action,
        ?string $dashboardDefinitionId = null,
    ): void {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::DashboardActivityLogged,
                summary: 'Dashboard activity logged',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::DashboardDefinition,
                entityPublicId: $dashboardDefinitionId,
                entityLabel: 'dashboard_activity',
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'action' => $action,
                    'dashboard_definition_id' => $dashboardDefinitionId,
                    'resource' => 'dashboard_activity',
                ],
            ));
        } catch (\Throwable) {
        }
    }

    private function recordDefinition(
        DashboardDefinitionModel $definition,
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
                entityType: AuditEntityType::DashboardDefinition,
                entityPublicId: $definition->public_id,
                entityLabel: $definition->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $definition->module_key,
                    'dashboard_key' => $definition->dashboard_key,
                    'entity_key' => $definition->entity_key,
                    'resource' => 'dashboard_definition',
                ],
            ));
        } catch (\Throwable) {
        }
    }

    private function recordWidget(
        DashboardWidget $widget,
        AuditAction $action,
        string $summary,
    ): void {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::DashboardWidget,
                entityPublicId: $widget->publicId,
                entityLabel: $widget->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'widget_key' => $widget->widgetKey,
                    'resource' => 'dashboard_widget',
                ],
            ));
        } catch (\Throwable) {
        }
    }

    private function recordView(
        DashboardView $view,
        AuditAction $action,
        string $summary,
    ): void {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id ?? $view->organizationId,
                workspaceId: $context?->workspace->id ?? $view->workspaceId,
                entityType: AuditEntityType::DashboardView,
                entityPublicId: $view->publicId,
                entityLabel: $view->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'resource' => 'dashboard_view',
                ],
            ));
        } catch (\Throwable) {
        }
    }
}
