<?php

namespace App\Services\Document;

use App\Models\EnterpriseAttachment;
use App\Modules\Sdk\Document\Contracts\AttachmentRepository;
use App\Modules\Sdk\Document\Data\AttachmentReference;
use App\Modules\Sdk\Document\Data\AttachmentRequest;
use App\Modules\Sdk\Document\Enums\AttachmentStatus;
use App\Modules\Sdk\Document\Exceptions\AttachmentException;
use App\Modules\Sdk\Document\Exceptions\DocumentNotFoundException;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnterpriseAttachmentService implements AttachmentRepository
{
    public function __construct(
        private readonly EnterpriseDocumentRepositoryService $repository,
        private readonly EnterpriseDocumentActivityService $activityService,
        private readonly EnterpriseDocumentAuditRecorder $auditRecorder,
        private readonly EnterpriseDocumentWorkflowBridge $workflowBridge,
    ) {
    }

    public function attach(string $organizationId, ?string $workspaceId, AttachmentRequest $request): AttachmentReference
    {
        return DB::transaction(function () use ($organizationId, $workspaceId, $request) {
            $document = $this->repository->resolveModel($organizationId, $workspaceId, $request->documentPublicId);
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $model = EnterpriseAttachment::query()->create([
                'id' => (string) Str::uuid7(),
                'enterprise_document_id' => $document->id,
                'document_public_id' => $document->public_id,
                'organization_id' => $organizationId,
                'workspace_id' => $workspaceId,
                'subject_type' => $request->subjectType,
                'subject_public_id' => $request->subjectPublicId,
                'subject_module_key' => $request->subjectModuleKey,
                'subject_entity_key' => $request->subjectEntityKey,
                'status' => AttachmentStatus::Active->value,
                'metadata' => $request->metadata,
                'created_by_user_id' => $context?->user->id,
                'created_by_membership_id' => $context?->membership->id,
            ]);

            $reference = EnterpriseDocumentMapper::toAttachmentReference($model);

            if ($context !== null) {
                $this->activityService->log(
                    organizationId: $organizationId,
                    workspaceId: $workspaceId,
                    documentPublicId: $document->public_id,
                    enterpriseDocumentId: $document->id,
                    action: 'attached',
                    afterState: $reference->toArray(),
                    userId: $context->user->id,
                    membershipId: $context->membership->id,
                );
                $this->auditRecorder->recordAttached($reference);
                $this->workflowBridge->triggerBestEffort($context, 'document.attached', $reference->toArray());
            }

            return $reference;
        });
    }

    /**
     * @return list<AttachmentReference>
     */
    public function listForDocument(string $organizationId, ?string $workspaceId, string $documentPublicId): array
    {
        $this->repository->resolveModel($organizationId, $workspaceId, $documentPublicId);

        return EnterpriseDocumentMapper::applyWorkspaceScope(
            EnterpriseAttachment::query()
                ->where('organization_id', $organizationId)
                ->where('document_public_id', $documentPublicId)
                ->where('status', AttachmentStatus::Active->value),
            $workspaceId,
        )
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (EnterpriseAttachment $model) => EnterpriseDocumentMapper::toAttachmentReference($model))
            ->all();
    }

    /**
     * @return list<AttachmentReference>
     */
    public function listForSubject(
        string $organizationId,
        ?string $workspaceId,
        string $subjectType,
        string $subjectPublicId,
    ): array {
        return EnterpriseDocumentMapper::applyWorkspaceScope(
            EnterpriseAttachment::query()
                ->where('organization_id', $organizationId)
                ->where('subject_type', $subjectType)
                ->where('subject_public_id', $subjectPublicId)
                ->where('status', AttachmentStatus::Active->value),
            $workspaceId,
        )
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (EnterpriseAttachment $model) => EnterpriseDocumentMapper::toAttachmentReference($model))
            ->all();
    }

    public function detach(string $organizationId, ?string $workspaceId, string $attachmentPublicId): AttachmentReference
    {
        return DB::transaction(function () use ($organizationId, $workspaceId, $attachmentPublicId) {
            $model = EnterpriseDocumentMapper::applyWorkspaceScope(
                EnterpriseAttachment::query()
                    ->where('organization_id', $organizationId)
                    ->where('public_id', $attachmentPublicId)
                    ->where('status', AttachmentStatus::Active->value),
                $workspaceId,
            )->first();

            if ($model === null) {
                throw new DocumentNotFoundException(sprintf('Attachment [%s] was not found.', $attachmentPublicId));
            }

            $model->status = AttachmentStatus::Detached;
            $model->save();
            $model->delete();

            $reference = EnterpriseDocumentMapper::toAttachmentReference(
                EnterpriseAttachment::query()->withTrashed()->where('public_id', $attachmentPublicId)->firstOrFail(),
            );

            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            if ($context !== null) {
                $this->activityService->log(
                    organizationId: $organizationId,
                    workspaceId: $workspaceId,
                    documentPublicId: $model->document_public_id,
                    enterpriseDocumentId: $model->enterprise_document_id,
                    action: 'detached',
                    beforeState: $reference->toArray(),
                    userId: $context->user->id,
                    membershipId: $context->membership->id,
                );
                $this->auditRecorder->recordDetached($reference);
                $this->workflowBridge->triggerBestEffort($context, 'document.detached', $reference->toArray());
            }

            return $reference;
        });
    }

    public function attachBestEffort(
        string $organizationId,
        ?string $workspaceId,
        AttachmentRequest $request,
    ): ?AttachmentReference {
        try {
            return $this->attach($organizationId, $workspaceId, $request);
        } catch (AttachmentException|\Throwable) {
            return null;
        }
    }
}
