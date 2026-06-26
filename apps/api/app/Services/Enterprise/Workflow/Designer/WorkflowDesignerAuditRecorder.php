<?php

namespace App\Services\Enterprise\Workflow\Designer;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\WorkflowCanvasSnapshot;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowNodeTemplate;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class WorkflowDesignerAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordCanvasSaved(WorkflowCanvasSnapshot $snapshot): void
    {
        $this->recordSnapshot($snapshot, AuditAction::WorkflowDesignerCanvasSaved, 'Workflow designer canvas saved');
    }

    public function recordSnapshotCreated(WorkflowCanvasSnapshot $snapshot): void
    {
        $this->recordSnapshot($snapshot, AuditAction::WorkflowDesignerSnapshotCreated, 'Workflow designer snapshot created');
    }

    public function recordSnapshotDiffed(WorkflowCanvasSnapshot $from, WorkflowCanvasSnapshot $to): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::WorkflowDesignerSnapshotDiffed,
                summary: 'Workflow designer snapshot diff generated',
                scope: AuditScope::Organization,
                organizationId: $from->organization_id,
                workspaceId: $from->workspace_id,
                entityType: AuditEntityType::WorkflowCanvasSnapshot,
                entityPublicId: $from->public_id,
                entityLabel: $from->workflowDefinition?->name ?? 'canvas',
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'from_snapshot_public_id' => $from->public_id,
                    'to_snapshot_public_id' => $to->public_id,
                ],
            ));
        } catch (\Throwable) {
        }
    }

    public function recordWorkflowCloned(WorkflowDefinition $source, WorkflowDefinition $clone): void
    {
        $this->recordDefinition($clone, AuditAction::WorkflowCloned, 'Workflow cloned', [
            'source_definition_public_id' => $source->public_id,
        ]);
    }

    public function recordWorkflowImported(WorkflowDefinition $definition): void
    {
        $this->recordDefinition($definition, AuditAction::WorkflowImported, 'Workflow imported');
    }

    public function recordWorkflowExported(WorkflowDefinition $definition): void
    {
        $this->recordDefinition($definition, AuditAction::WorkflowExported, 'Workflow exported');
    }

    public function recordTemplateCreated(WorkflowNodeTemplate $template): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::WorkflowDesignerTemplateCreated,
                summary: 'Workflow node template created',
                scope: AuditScope::Organization,
                organizationId: $template->organization_id,
                workspaceId: $template->workspace_id,
                entityType: AuditEntityType::WorkflowNodeTemplate,
                entityPublicId: $template->public_id,
                entityLabel: $template->label,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: ['node_type' => $template->node_type],
            ));
        } catch (\Throwable) {
        }
    }

    public function recordPreviewGenerated(WorkflowDefinition $definition): void
    {
        $this->recordDefinition($definition, AuditAction::WorkflowDesignerPreviewGenerated, 'Workflow designer preview generated');
    }

    private function recordSnapshot(
        WorkflowCanvasSnapshot $snapshot,
        AuditAction $action,
        string $summary,
    ): void {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $snapshot->organization_id,
                workspaceId: $snapshot->workspace_id,
                entityType: AuditEntityType::WorkflowCanvasSnapshot,
                entityPublicId: $snapshot->public_id,
                entityLabel: $snapshot->workflowDefinition?->name ?? 'canvas',
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'workflow_definition_public_id' => $snapshot->workflowDefinition?->public_id,
                ],
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
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: array_merge(['workflow_key' => $definition->workflow_key], $metadata),
            ));
        } catch (\Throwable) {
        }
    }
}
