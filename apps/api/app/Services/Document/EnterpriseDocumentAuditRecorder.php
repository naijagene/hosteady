<?php

namespace App\Services\Document;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Modules\Sdk\Document\Data\AttachmentReference;
use App\Modules\Sdk\Document\Data\DocumentOcrResult;
use App\Modules\Sdk\Document\Data\DocumentPreview;
use App\Modules\Sdk\Document\Data\DocumentReference;
use App\Modules\Sdk\Document\Data\DocumentScanResult;
use App\Modules\Sdk\Document\Data\DocumentThumbnail;
use App\Modules\Sdk\Document\Data\DocumentVersionReference;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class EnterpriseDocumentAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordUploaded(DocumentReference $document): void
    {
        $this->recordDocument($document, AuditAction::DocumentUploaded, 'Document uploaded');
    }

    public function recordUpdated(DocumentReference $document): void
    {
        $this->recordDocument($document, AuditAction::DocumentUpdated, 'Document updated');
    }

    public function recordDeleted(DocumentReference $document): void
    {
        $this->recordDocument($document, AuditAction::DocumentDeleted, 'Document deleted');
    }

    public function recordVersionCreated(DocumentVersionReference $version): void
    {
        $this->recordVersion($version, AuditAction::DocumentVersionCreated, 'Document version created');
    }

    public function recordVersionRestored(DocumentVersionReference $version): void
    {
        $this->recordVersion($version, AuditAction::DocumentVersionRestored, 'Document version restored');
    }

    public function recordVersionDeleted(DocumentVersionReference $version): void
    {
        $this->recordVersion($version, AuditAction::DocumentVersionDeleted, 'Document version deleted');
    }

    public function recordAttached(AttachmentReference $attachment): void
    {
        $this->recordAttachment($attachment, AuditAction::DocumentAttached, 'Document attached');
    }

    public function recordDetached(AttachmentReference $attachment): void
    {
        $this->recordAttachment($attachment, AuditAction::DocumentDetached, 'Document detached');
    }

    public function recordPreviewRequested(DocumentPreview $preview): void
    {
        $this->recordReference($preview->toArray(), AuditAction::DocumentPreviewRequested, 'Document preview requested', $preview->documentPublicId);
    }

    public function recordThumbnailRequested(DocumentThumbnail $thumbnail): void
    {
        $this->recordReference($thumbnail->toArray(), AuditAction::DocumentThumbnailRequested, 'Document thumbnail requested', $thumbnail->documentPublicId);
    }

    public function recordScanRequested(DocumentScanResult $scan): void
    {
        $this->recordReference($scan->toArray(), AuditAction::DocumentScanRequested, 'Document scan requested', $scan->documentPublicId);
    }

    public function recordOcrRequested(DocumentOcrResult $ocr): void
    {
        $this->recordReference($ocr->toArray(), AuditAction::DocumentOcrRequested, 'Document OCR requested', $ocr->documentPublicId);
    }

    public function recordActivityLogged(string $documentPublicId, string $action): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::DocumentActivityLogged,
                summary: 'Document activity logged',
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace?->id,
                entityType: AuditEntityType::EnterpriseDocument,
                entityPublicId: $documentPublicId,
                entityLabel: $documentPublicId,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'action' => $action,
                ],
            ));
        } catch (\Throwable) {
        }
    }

    private function recordDocument(DocumentReference $document, AuditAction $action, string $summary): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id ?? $document->organizationId,
                workspaceId: $context?->workspace?->id ?? $document->workspaceId,
                entityType: AuditEntityType::EnterpriseDocument,
                entityPublicId: $document->publicId,
                entityLabel: $document->title,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: [
                    'category' => $document->category,
                    'status' => $document->status,
                    'current_version_number' => $document->currentVersionNumber,
                ],
            ));
        } catch (\Throwable) {
        }
    }

    private function recordVersion(DocumentVersionReference $version, AuditAction $action, string $summary): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace?->id,
                entityType: AuditEntityType::EnterpriseDocument,
                entityPublicId: $version->documentPublicId,
                entityLabel: sprintf('version:%d', $version->versionNumber),
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: $version->toArray(),
            ));
        } catch (\Throwable) {
        }
    }

    private function recordAttachment(AttachmentReference $attachment, AuditAction $action, string $summary): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace?->id,
                entityType: AuditEntityType::EnterpriseDocument,
                entityPublicId: $attachment->documentPublicId,
                entityLabel: $attachment->subjectType,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: $attachment->toArray(),
            ));
        } catch (\Throwable) {
        }
    }

    /**
     * @param  array<string, mixed>  $reference
     */
    private function recordReference(array $reference, AuditAction $action, string $summary, string $documentPublicId): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace?->id,
                entityType: AuditEntityType::EnterpriseDocument,
                entityPublicId: $documentPublicId,
                entityLabel: $documentPublicId,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: $reference,
            ));
        } catch (\Throwable) {
        }
    }
}
