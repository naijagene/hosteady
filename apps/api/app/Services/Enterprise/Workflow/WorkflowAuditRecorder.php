<?php

namespace App\Services\Enterprise\Workflow;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\WorkflowCategory;
use App\Models\WorkflowDefinition;
use App\Modules\Sdk\Workflow\Data\WorkflowValidationReport;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class WorkflowAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordCreated(WorkflowDefinition $definition): void
    {
        $this->recordDefinition($definition, AuditAction::WorkflowCreated, sprintf('Workflow %s created', $definition->name));
    }

    public function recordUpdated(WorkflowDefinition $definition): void
    {
        $this->recordDefinition($definition, AuditAction::WorkflowUpdated, sprintf('Workflow %s updated', $definition->name));
    }

    public function recordPublished(WorkflowDefinition $definition): void
    {
        $this->recordDefinition($definition, AuditAction::WorkflowPublished, sprintf('Workflow %s published', $definition->name));
    }

    public function recordArchived(WorkflowDefinition $definition): void
    {
        $this->recordDefinition($definition, AuditAction::WorkflowArchived, sprintf('Workflow %s archived', $definition->name));
    }

    public function recordValidated(WorkflowDefinition $definition, WorkflowValidationReport $report): void
    {
        $this->recordDefinition(
            $definition,
            AuditAction::WorkflowValidated,
            sprintf('Workflow %s validated', $definition->name),
            ['valid' => $report->valid, 'issue_count' => count($report->issues)],
        );
    }

    public function recordCategoryCreated(WorkflowCategory $category): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::WorkflowCategoryCreated,
                summary: sprintf('Workflow category %s created', $category->name),
                scope: AuditScope::Organization,
                organizationId: $category->organization_id,
                workspaceId: $category->workspace_id,
                entityType: AuditEntityType::WorkflowCategory,
                entityPublicId: $category->public_id,
                entityLabel: $category->name,
                metadata: ['category_key' => $category->category_key],
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                retentionClass: AuditRetentionClass::Ephemeral,
                severity: AuditSeverity::Info,
            ));
        } catch (\Throwable) {
        }
    }

    public function recordCategoryUpdated(WorkflowCategory $category): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::WorkflowCategoryUpdated,
                summary: sprintf('Workflow category %s updated', $category->name),
                scope: AuditScope::Organization,
                organizationId: $category->organization_id,
                workspaceId: $category->workspace_id,
                entityType: AuditEntityType::WorkflowCategory,
                entityPublicId: $category->public_id,
                entityLabel: $category->name,
                metadata: ['category_key' => $category->category_key],
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                retentionClass: AuditRetentionClass::Ephemeral,
                severity: AuditSeverity::Info,
            ));
        } catch (\Throwable) {
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordDefinition(
        WorkflowDefinition $definition,
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
                organizationId: $definition->organization_id,
                workspaceId: $definition->workspace_id,
                entityType: AuditEntityType::WorkflowDefinition,
                entityPublicId: $definition->public_id,
                entityLabel: $definition->name,
                metadata: array_merge([
                    'workflow_key' => $definition->workflow_key,
                    'status' => $definition->status->value,
                ], $metadata),
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
