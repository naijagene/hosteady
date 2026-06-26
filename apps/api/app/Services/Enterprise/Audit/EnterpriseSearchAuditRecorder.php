<?php

namespace App\Services\Enterprise\Audit;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\PlatformSavedSearch;
use App\Models\PlatformSearchIndex;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class EnterpriseSearchAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordExecuted(TenantContext $context, ?string $query, int $resultCount, ?string $moduleKey = null): void
    {
        $this->recordContext(
            $context,
            AuditAction::SearchExecuted,
            sprintf('Search executed with %d result(s)', $resultCount),
            $context->membershipPublicId,
            [
                'query' => $query,
                'result_count' => $resultCount,
                'module_key' => $moduleKey,
            ],
        );
    }

    public function recordSaved(PlatformSavedSearch $savedSearch): void
    {
        $this->recordEntity(
            $savedSearch,
            AuditAction::SearchSaved,
            sprintf('Saved search %s created', $savedSearch->name),
        );
    }

    public function recordDeleted(PlatformSavedSearch $savedSearch): void
    {
        $this->recordEntity(
            $savedSearch,
            AuditAction::SearchDeleted,
            sprintf('Saved search %s deleted', $savedSearch->name),
        );
    }

    public function recordIndexUpdated(PlatformSearchIndex $index): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::IndexUpdated,
                summary: sprintf('Search index updated for %s', $index->display_name),
                scope: AuditScope::Organization,
                organizationId: $index->organization_id,
                workspaceId: $index->workspace_id,
                entityType: AuditEntityType::PlatformSearchIndex,
                entityPublicId: $index->public_id,
                entityLabel: $index->display_name,
                metadata: [
                    'module_key' => $index->module_key,
                    'entity_type' => $index->entity_type,
                ],
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                retentionClass: AuditRetentionClass::Ephemeral,
                severity: AuditSeverity::Info,
            ));
        } catch (\Throwable) {
        }
    }

    private function recordContext(
        TenantContext $context,
        AuditAction $action,
        string $summary,
        string $entityPublicId,
        array $metadata,
    ): void {
        try {
            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context->organization->id,
                workspaceId: $context->workspace->id,
                entityType: AuditEntityType::PlatformSearchIndex,
                entityPublicId: $entityPublicId,
                entityLabel: $summary,
                metadata: $metadata,
                actorType: AuditActorType::User,
                actorUserId: $context->user->id,
                actorMembershipId: $context->membership->id,
                retentionClass: AuditRetentionClass::Ephemeral,
                severity: AuditSeverity::Info,
            ));
        } catch (\Throwable) {
        }
    }

    private function recordEntity(PlatformSavedSearch $savedSearch, AuditAction $action, string $summary): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $savedSearch->organization_id,
                workspaceId: $savedSearch->workspace_id,
                entityType: AuditEntityType::PlatformSavedSearch,
                entityPublicId: $savedSearch->public_id,
                entityLabel: $savedSearch->name,
                metadata: [
                    'query' => $savedSearch->query,
                    'module_key' => $savedSearch->module_key,
                ],
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                retentionClass: AuditRetentionClass::Ephemeral,
                severity: AuditSeverity::Info,
            ));
        } catch (\Throwable) {
        }
    }
}
