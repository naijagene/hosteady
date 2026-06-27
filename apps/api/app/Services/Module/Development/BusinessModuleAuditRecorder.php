<?php

namespace App\Services\Module\Development;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\BusinessModule;
use App\Models\BusinessModuleInstallation;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class BusinessModuleAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordRegistered(BusinessModule $module): void
    {
        $this->recordModule($module, AuditAction::BusinessModuleRegistered, 'Business module registered');
    }

    public function recordInstalled(BusinessModuleInstallation $installation): void
    {
        $this->recordInstallation($installation, AuditAction::BusinessModuleInstalled, 'Business module installed');
    }

    public function recordEnabled(BusinessModuleInstallation $installation): void
    {
        $this->recordInstallation($installation, AuditAction::BusinessModuleEnabled, 'Business module enabled');
    }

    public function recordDisabled(BusinessModuleInstallation $installation): void
    {
        $this->recordInstallation($installation, AuditAction::BusinessModuleDisabled, 'Business module disabled');
    }

    public function recordUninstalled(BusinessModuleInstallation $installation): void
    {
        $this->recordInstallation($installation, AuditAction::BusinessModuleUninstalled, 'Business module uninstalled');
    }

    public function recordScaffolded(string $moduleKey): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::BusinessModuleScaffolded,
                summary: 'Business module scaffolded',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::BusinessModule,
                entityPublicId: $moduleKey,
                entityLabel: $moduleKey,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: ['module_key' => $moduleKey],
            ));
        } catch (\Throwable) {
        }
    }

    public function recordValidated(string $moduleKey): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::BusinessModuleValidated,
                summary: 'Business module manifest validated',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::BusinessModule,
                entityPublicId: $moduleKey,
                entityLabel: $moduleKey,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: ['module_key' => $moduleKey],
            ));
        } catch (\Throwable) {
        }
    }

    private function recordModule(BusinessModule $module, AuditAction $action, string $summary): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::BusinessModule,
                entityPublicId: $module->public_id,
                entityLabel: $module->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: ['module_key' => $module->module_key],
            ));
        } catch (\Throwable) {
        }
    }

    private function recordInstallation(
        BusinessModuleInstallation $installation,
        AuditAction $action,
        string $summary,
    ): void {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;
            $module = $installation->businessModule;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $installation->organization_id,
                workspaceId: $installation->workspace_id,
                entityType: AuditEntityType::BusinessModuleInstallation,
                entityPublicId: $installation->public_id,
                entityLabel: $module?->name ?? 'Business module installation',
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_public_id' => $module?->public_id,
                    'module_key' => $module?->module_key,
                    'installed_version' => $installation->installed_version,
                ],
            ));
        } catch (\Throwable) {
        }
    }
}
