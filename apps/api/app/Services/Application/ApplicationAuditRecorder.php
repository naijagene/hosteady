<?php

namespace App\Services\Application;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Modules\Sdk\Application\Data\ApplicationDefinition;
use App\Modules\Sdk\Application\Data\ApplicationWorkspace;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class ApplicationAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordRegistered(ApplicationDefinition $application): void
    {
        $this->record($application->publicId, AuditAction::ApplicationRuntimeRegistered, 'Application registered', $application->toArray());
    }

    public function recordEnabled(ApplicationDefinition $application): void
    {
        $this->record($application->publicId, AuditAction::ApplicationRuntimeEnabled, 'Application enabled', $application->toArray());
    }

    public function recordDisabled(ApplicationDefinition $application): void
    {
        $this->record($application->publicId, AuditAction::ApplicationRuntimeDisabled, 'Application disabled', $application->toArray());
    }

    public function recordNavigationUpdated(string $menuKey, array $metadata = []): void
    {
        $this->record($menuKey, AuditAction::ApplicationNavigationUpdated, 'Application navigation updated', $metadata);
    }

    public function recordWorkspaceCreated(ApplicationWorkspace $workspace): void
    {
        $this->record($workspace->publicId, AuditAction::ApplicationWorkspaceCreated, 'Application workspace created', $workspace->toArray());
    }

    private function record(string $entityPublicId, AuditAction $action, string $summary, array $metadata, ?TenantContext $context = null): void
    {
        try {
            $context ??= app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace?->id,
                entityType: AuditEntityType::ApplicationRuntime,
                entityPublicId: $entityPublicId,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: $metadata,
            ));
        } catch (\Throwable) {
        }
    }
}
