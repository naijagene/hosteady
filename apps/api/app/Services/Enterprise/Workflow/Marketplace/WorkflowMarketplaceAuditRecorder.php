<?php

namespace App\Services\Enterprise\Workflow\Marketplace;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\WorkflowPackage;
use App\Models\WorkflowPackageInstall;
use App\Models\WorkflowPackageVersion;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class WorkflowMarketplaceAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordCreated(WorkflowPackage $package): void
    {
        $this->recordPackage($package, AuditAction::WorkflowMarketplacePackageCreated, 'Workflow marketplace package created');
    }

    public function recordUpdated(WorkflowPackage $package): void
    {
        $this->recordPackage($package, AuditAction::WorkflowMarketplacePackageUpdated, 'Workflow marketplace package updated');
    }

    public function recordDeleted(WorkflowPackage $package): void
    {
        $this->recordPackage($package, AuditAction::WorkflowMarketplacePackageDeleted, 'Workflow marketplace package deleted');
    }

    public function recordVersionPublished(WorkflowPackage $package, WorkflowPackageVersion $version): void
    {
        $this->recordPackage($package, AuditAction::WorkflowMarketplacePackageVersionPublished, 'Workflow marketplace package version published', [
            'version' => $version->version,
            'version_public_id' => $version->public_id,
        ]);
    }

    public function recordInstalled(WorkflowPackageInstall $install): void
    {
        $this->recordInstall($install, AuditAction::WorkflowMarketplaceInstalled, 'Workflow marketplace package installed');
    }

    public function recordUpgraded(WorkflowPackageInstall $install): void
    {
        $this->recordInstall($install, AuditAction::WorkflowMarketplaceUpgraded, 'Workflow marketplace package upgraded');
    }

    public function recordRollback(WorkflowPackageInstall $install): void
    {
        $this->recordInstall($install, AuditAction::WorkflowMarketplaceRollback, 'Workflow marketplace package rolled back');
    }

    public function recordUninstalled(WorkflowPackageInstall $install): void
    {
        $this->recordInstall($install, AuditAction::WorkflowMarketplaceUninstalled, 'Workflow marketplace package uninstalled');
    }

    public function recordExported(WorkflowPackage $package): void
    {
        $this->recordPackage($package, AuditAction::WorkflowMarketplaceExported, 'Workflow marketplace package exported');
    }

    public function recordImported(WorkflowPackage $package): void
    {
        $this->recordPackage($package, AuditAction::WorkflowMarketplaceImported, 'Workflow marketplace package imported');
    }

    public function recordCompatibilityChecked(WorkflowPackage $package): void
    {
        $this->recordPackage($package, AuditAction::WorkflowMarketplaceCompatibilityChecked, 'Workflow marketplace compatibility checked');
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordPackage(
        WorkflowPackage $package,
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
                organizationId: $package->organization_id,
                workspaceId: $package->workspace_id,
                entityType: AuditEntityType::WorkflowPackage,
                entityPublicId: $package->public_id,
                entityLabel: $package->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: array_merge(['package_key' => $package->package_key], $metadata),
            ));
        } catch (\Throwable) {
        }
    }

    private function recordInstall(
        WorkflowPackageInstall $install,
        AuditAction $action,
        string $summary,
    ): void {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;
            $package = $install->workflowPackage;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $install->organization_id,
                workspaceId: $install->workspace_id,
                entityType: AuditEntityType::WorkflowPackageInstall,
                entityPublicId: $install->public_id,
                entityLabel: $package?->name ?? 'Package install',
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'package_public_id' => $package?->public_id,
                    'installed_version' => $install->installed_version,
                    'workflow_definition_public_id' => $install->installedWorkflowDefinition?->public_id,
                ],
            ));
        } catch (\Throwable) {
        }
    }
}
