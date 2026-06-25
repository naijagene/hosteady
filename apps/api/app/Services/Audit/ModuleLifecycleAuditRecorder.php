<?php

namespace App\Services\Audit;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class ModuleLifecycleAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordInstallCompleted(TenantContext $context, string $moduleKey): void
    {
        $this->recordModuleEvent(
            action: AuditAction::ModuleInstallCompleted,
            context: $context,
            moduleKey: $moduleKey,
            summary: sprintf('Module %s install completed', $moduleKey),
        );
    }

    public function recordUninstallCompleted(TenantContext $context, string $moduleKey): void
    {
        $this->recordModuleEvent(
            action: AuditAction::ModuleUninstallCompleted,
            context: $context,
            moduleKey: $moduleKey,
            summary: sprintf('Module %s uninstall completed', $moduleKey),
        );
    }

    public function recordWorkspaceEnabled(TenantContext $context, string $moduleKey): void
    {
        $this->recordModuleEvent(
            action: AuditAction::ModuleWorkspaceEnabled,
            context: $context,
            moduleKey: $moduleKey,
            summary: sprintf('Module %s workspace enabled', $moduleKey),
        );
    }

    public function recordWorkspaceDisabled(TenantContext $context, string $moduleKey): void
    {
        $this->recordModuleEvent(
            action: AuditAction::ModuleWorkspaceDisabled,
            context: $context,
            moduleKey: $moduleKey,
            summary: sprintf('Module %s workspace disabled', $moduleKey),
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function recordSettingsUpdated(TenantContext $context, string $moduleKey, array $metadata = []): void
    {
        $this->recordModuleEvent(
            action: AuditAction::ModuleSettingsUpdated,
            context: $context,
            moduleKey: $moduleKey,
            summary: sprintf('Module %s settings updated', $moduleKey),
            metadata: $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function recordRuntimeBefore(TenantContext $context, string $moduleKey, array $metadata = []): void
    {
        $this->recordModuleEvent(
            action: AuditAction::ModuleRuntimeBefore,
            context: $context,
            moduleKey: $moduleKey,
            summary: sprintf('Module %s runtime before resolved', $moduleKey),
            metadata: $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function recordRuntimeAfter(TenantContext $context, string $moduleKey, array $metadata = []): void
    {
        $this->recordModuleEvent(
            action: AuditAction::ModuleRuntimeAfter,
            context: $context,
            moduleKey: $moduleKey,
            summary: sprintf('Module %s runtime after resolved', $moduleKey),
            metadata: $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordModuleEvent(
        AuditAction $action,
        TenantContext $context,
        string $moduleKey,
        string $summary,
        array $metadata = [],
    ): void {
        $this->auditEventRecorder->record(new AuditEventData(
            action: $action,
            summary: $summary,
            scope: AuditScope::Organization,
            organizationId: $context->organization->id,
            workspaceId: $context->workspace->id,
            entityType: AuditEntityType::Application,
            entityPublicId: $metadata['application_public_id'] ?? $moduleKey,
            entityLabel: $moduleKey,
            metadata: array_merge(['module_key' => $moduleKey], $metadata),
            actorType: AuditActorType::User,
            actorUserId: $context->user->id,
            actorMembershipId: $context->membership->id,
            retentionClass: AuditRetentionClass::Ephemeral,
            severity: AuditSeverity::Info,
        ));
    }
}
