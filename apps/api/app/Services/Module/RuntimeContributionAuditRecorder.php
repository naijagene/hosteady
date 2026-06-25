<?php

namespace App\Services\Module;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class RuntimeContributionAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordContribution(TenantContext $context, string $moduleKey): void
    {
        try {
            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::ModuleRuntimeContribution,
                summary: sprintf('Module %s runtime contribution merged', $moduleKey),
                scope: AuditScope::Organization,
                organizationId: $context->organization->id,
                workspaceId: $context->workspace->id,
                entityType: AuditEntityType::Application,
                entityPublicId: $moduleKey,
                entityLabel: $moduleKey,
                metadata: ['module_key' => $moduleKey],
                actorType: AuditActorType::User,
                actorUserId: $context->user->id,
                actorMembershipId: $context->membership->id,
                retentionClass: AuditRetentionClass::Ephemeral,
                severity: AuditSeverity::Info,
            ));
        } catch (\Throwable) {
            // Audit failures must never stop runtime generation.
        }
    }
}
