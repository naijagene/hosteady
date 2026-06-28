<?php

namespace App\Services\Report;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\ReportDefinition as ReportDefinitionModel;
use App\Modules\Sdk\Report\Data\ReportDefinition;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class DynamicReportAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordDefinitionRegistered(ReportDefinitionModel $definition): void
    {
        $this->recordDefinition($definition, AuditAction::ReportDefinitionRegistered, 'Report definition registered');
    }

    public function recordDefinitionUpdated(ReportDefinitionModel $definition): void
    {
        $this->recordDefinition($definition, AuditAction::ReportDefinitionUpdated, 'Report definition updated');
    }

    public function recordRendered(ReportDefinition $definition): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::ReportRendered,
                summary: 'Report rendered',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::ReportDefinition,
                entityPublicId: $definition->publicId,
                entityLabel: $definition->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $definition->moduleKey,
                    'report_key' => $definition->reportKey,
                    'entity_key' => $definition->entityKey,
                    'resource' => 'report_definition',
                ],
            ));
        } catch (\Throwable) {
        }
    }

    public function recordRunStarted(ReportDefinition $definition, ?string $runPublicId = null): void
    {
        $this->recordRun($definition, AuditAction::ReportRunStarted, 'Report run started', $runPublicId);
    }

    public function recordRunCompleted(ReportDefinition $definition, ?string $runPublicId = null): void
    {
        $this->recordRun($definition, AuditAction::ReportRunCompleted, 'Report run completed', $runPublicId);
    }

    public function recordRunFailed(ReportDefinition $definition, ?string $runPublicId = null): void
    {
        $this->recordRun($definition, AuditAction::ReportRunFailed, 'Report run failed', $runPublicId);
    }

    public function recordExportRequested(ReportDefinition $definition, string $format): void
    {
        $this->recordExport($definition, AuditAction::ReportExportRequested, 'Report export requested', $format);
    }

    public function recordExportCompleted(ReportDefinition $definition, ?string $exportPublicId = null): void
    {
        $this->recordExport($definition, AuditAction::ReportExportCompleted, 'Report export completed', null, $exportPublicId);
    }

    public function recordExportFailed(ReportDefinition $definition, string $format): void
    {
        $this->recordExport($definition, AuditAction::ReportExportFailed, 'Report export failed', $format);
    }

    public function recordScheduleCreated(string $moduleKey, string $reportKey, ?string $schedulePublicId = null): void
    {
        $this->recordSchedule($moduleKey, $reportKey, AuditAction::ReportScheduleCreated, 'Report schedule created', $schedulePublicId);
    }

    public function recordScheduleUpdated(string $moduleKey, string $reportKey, ?string $schedulePublicId = null): void
    {
        $this->recordSchedule($moduleKey, $reportKey, AuditAction::ReportScheduleUpdated, 'Report schedule updated', $schedulePublicId);
    }

    public function recordScheduleDeleted(string $moduleKey, string $reportKey, ?string $schedulePublicId = null): void
    {
        $this->recordSchedule($moduleKey, $reportKey, AuditAction::ReportScheduleDeleted, 'Report schedule deleted', $schedulePublicId);
    }

    public function recordActivityLogged(string $action, ?string $reportDefinitionId = null): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::ReportActivityLogged,
                summary: 'Report activity logged',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::ReportDefinition,
                entityPublicId: $reportDefinitionId,
                entityLabel: 'report_activity',
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'action' => $action,
                    'report_definition_id' => $reportDefinitionId,
                    'resource' => 'report_activity',
                ],
            ));
        } catch (\Throwable) {
        }
    }

    private function recordDefinition(
        ReportDefinitionModel $definition,
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
                entityType: AuditEntityType::ReportDefinition,
                entityPublicId: $definition->public_id,
                entityLabel: $definition->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $definition->module_key,
                    'report_key' => $definition->report_key,
                    'entity_key' => $definition->entity_key,
                    'resource' => 'report_definition',
                ],
            ));
        } catch (\Throwable) {
        }
    }

    private function recordRun(
        ReportDefinition $definition,
        AuditAction $action,
        string $summary,
        ?string $runPublicId = null,
    ): void {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::ReportRun,
                entityPublicId: $runPublicId,
                entityLabel: $definition->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $definition->moduleKey,
                    'report_key' => $definition->reportKey,
                    'resource' => 'report_run',
                ],
            ));
        } catch (\Throwable) {
        }
    }

    private function recordExport(
        ReportDefinition $definition,
        AuditAction $action,
        string $summary,
        ?string $format = null,
        ?string $exportPublicId = null,
    ): void {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::ReportExport,
                entityPublicId: $exportPublicId,
                entityLabel: $definition->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $definition->moduleKey,
                    'report_key' => $definition->reportKey,
                    'export_format' => $format,
                    'resource' => 'report_export',
                ],
            ));
        } catch (\Throwable) {
        }
    }

    private function recordSchedule(
        string $moduleKey,
        string $reportKey,
        AuditAction $action,
        string $summary,
        ?string $schedulePublicId = null,
    ): void {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::ReportSchedule,
                entityPublicId: $schedulePublicId,
                entityLabel: sprintf('%s.%s', $moduleKey, $reportKey),
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $moduleKey,
                    'report_key' => $reportKey,
                    'resource' => 'report_schedule',
                ],
            ));
        } catch (\Throwable) {
        }
    }
}
