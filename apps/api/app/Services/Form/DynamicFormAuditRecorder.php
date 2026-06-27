<?php

namespace App\Services\Form;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\FormDefinition as FormDefinitionModel;
use App\Models\FormSubmission;
use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class DynamicFormAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordDefinitionRegistered(FormDefinitionModel $definition): void
    {
        $this->recordDefinition($definition, 'Form definition registered');
    }

    public function recordDefinitionUpdated(FormDefinitionModel $definition): void
    {
        $this->recordDefinition($definition, 'Form definition updated');
    }

    public function recordValidated(FormDefinition $definition): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::FormValidated,
                summary: 'Form definition validated',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::FormDefinition,
                entityPublicId: $definition->publicId,
                entityLabel: $definition->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $definition->moduleKey,
                    'form_key' => $definition->formKey,
                    'entity_key' => $definition->entityKey,
                    'resource' => 'form_definition',
                ],
            ));
        } catch (\Throwable) {
        }
    }

    public function recordSubmission(FormSubmission $submission): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::FormSubmitted,
                summary: 'Form submission accepted',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id ?? $submission->organization_id,
                workspaceId: $context?->workspace->id ?? $submission->workspace_id,
                entityType: AuditEntityType::FormSubmission,
                entityPublicId: $submission->public_id,
                entityLabel: sprintf('%s.%s', $submission->module_key, $submission->entity_key ?? 'form'),
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id ?? $submission->submitted_by_user_id,
                actorMembershipId: $context?->membership->id ?? $submission->submitted_membership_id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $submission->module_key,
                    'entity_key' => $submission->entity_key,
                    'entity_public_id' => $submission->entity_public_id,
                    'status' => $submission->status,
                    'resource' => 'form_submission',
                ],
            ));
        } catch (\Throwable) {
        }
    }

    public function recordActivityLogged(
        string $action,
        ?string $formDefinitionId = null,
        ?string $formSubmissionId = null,
    ): void {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::FormActivityLogged,
                summary: 'Form activity logged',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::FormDefinition,
                entityPublicId: $formSubmissionId ?? $formDefinitionId,
                entityLabel: 'form_activity',
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'action' => $action,
                    'form_definition_id' => $formDefinitionId,
                    'form_submission_id' => $formSubmissionId,
                    'resource' => 'form_activity',
                ],
            ));
        } catch (\Throwable) {
        }
    }

    public function recordDraftSaved(?string $formPublicId = null): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::FormDraftSaved,
                summary: 'Form draft saved',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace->id,
                entityType: AuditEntityType::FormDraft,
                entityPublicId: $formPublicId,
                entityLabel: 'form_draft',
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: ['resource' => 'form_draft'],
            ));
        } catch (\Throwable) {
        }
    }

    private function recordDefinition(FormDefinitionModel $definition, string $summary): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $summary === 'Form definition updated'
                    ? AuditAction::FormDefinitionUpdated
                    : AuditAction::FormDefinitionRegistered,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id ?? $definition->organization_id,
                workspaceId: $context?->workspace->id ?? $definition->workspace_id,
                entityType: AuditEntityType::FormDefinition,
                entityPublicId: $definition->public_id,
                entityLabel: $definition->name,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'module_key' => $definition->module_key,
                    'form_key' => $definition->form_key,
                    'entity_key' => $definition->entity_key,
                    'resource' => 'form_definition',
                ],
            ));
        } catch (\Throwable) {
        }
    }
}
