<?php

namespace App\Services\Document;

use App\Modules\Sdk\Document\Contracts\AttachmentRepository;
use App\Modules\Sdk\Document\Contracts\DocumentOcrProvider;
use App\Modules\Sdk\Document\Contracts\DocumentPreviewProvider;
use App\Modules\Sdk\Document\Contracts\DocumentRepository;
use App\Modules\Sdk\Document\Contracts\DocumentRetentionPolicy;
use App\Modules\Sdk\Document\Contracts\DocumentScanner;
use App\Modules\Sdk\Document\Contracts\DocumentThumbnailProvider;
use App\Modules\Sdk\Document\Contracts\DocumentVersionManager;
use App\Modules\Sdk\Document\Data\AttachmentReference;
use App\Modules\Sdk\Document\Data\AttachmentRequest;
use App\Modules\Sdk\Document\Data\DocumentHealthReport;
use App\Modules\Sdk\Document\Data\DocumentOcrResult;
use App\Modules\Sdk\Document\Data\DocumentPreview;
use App\Modules\Sdk\Document\Data\DocumentQuotaReport;
use App\Modules\Sdk\Document\Data\DocumentReference;
use App\Modules\Sdk\Document\Data\DocumentRetentionRule;
use App\Modules\Sdk\Document\Data\DocumentScanResult;
use App\Modules\Sdk\Document\Data\DocumentStatistics;
use App\Modules\Sdk\Document\Data\DocumentThumbnail;
use App\Modules\Sdk\Document\Data\DocumentUpdateRequest;
use App\Modules\Sdk\Document\Data\DocumentUploadRequest;
use App\Modules\Sdk\Document\Data\DocumentVersionReference;
use App\Modules\Sdk\Document\Data\DocumentVersionRequest;
use App\Modules\Sdk\Document\Exceptions\DocumentNotFoundException;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EnterpriseDocumentDevelopmentService
{
    public function __construct(
        private readonly DocumentRepository $repository,
        private readonly EnterpriseDocumentUploadService $uploadService,
        private readonly DocumentVersionManager $versionManager,
        private readonly AttachmentRepository $attachmentRepository,
        private readonly DocumentPreviewProvider $previewProvider,
        private readonly DocumentThumbnailProvider $thumbnailProvider,
        private readonly DocumentScanner $scanner,
        private readonly DocumentOcrProvider $ocrProvider,
        private readonly DocumentRetentionPolicy $retentionPolicy,
        private readonly EnterpriseDocumentPermissionService $permissionService,
        private readonly EnterpriseDocumentQuotaService $quotaService,
        private readonly EnterpriseDocumentActivityService $activityService,
        private readonly EnterpriseDocumentHealthService $healthService,
        private readonly EnterpriseDocumentStatisticsService $statisticsService,
        private readonly EnterpriseDocumentAuditRecorder $auditRecorder,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
    ) {
    }

    /**
     * @return list<DocumentReference>
     */
    public function list(TenantContext $context, int $limit = 50): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->repository->list(
            $context->organization->id,
            $context->workspace?->id,
            $limit,
        );
    }

    public function show(TenantContext $context, string $documentPublicId): DocumentReference
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $document = $this->repository->find(
            $context->organization->id,
            $context->workspace?->id,
            $documentPublicId,
        );

        if ($document === null) {
            throw new DocumentNotFoundException(sprintf('Document [%s] was not found.', $documentPublicId));
        }

        return $document;
    }

    public function upload(TenantContext $context, DocumentUploadRequest $request): DocumentReference
    {
        $this->requireCapability($context);
        $this->assertUpload($context);
        $this->quotaService->assertWithinQuota($context->organization, $context->workspace);

        return $this->uploadService->upload($context, $request);
    }

    public function update(TenantContext $context, DocumentUpdateRequest $request): DocumentReference
    {
        $this->requireCapability($context);
        $this->assertUpdate($context);

        $before = $this->show($context, $request->documentPublicId);
        $document = $this->repository->update(
            $context->organization->id,
            $context->workspace?->id,
            $request,
        );

        $this->activityService->log(
            organizationId: $context->organization->id,
            workspaceId: $context->workspace?->id,
            documentPublicId: $document->publicId,
            action: 'updated',
            beforeState: $before->toArray(),
            afterState: $document->toArray(),
            userId: $context->user->id,
            membershipId: $context->membership->id,
        );
        $this->auditRecorder->recordUpdated($document);

        return $document;
    }

    public function delete(TenantContext $context, string $documentPublicId): DocumentReference
    {
        $this->requireCapability($context);
        $this->assertDelete($context);

        $before = $this->show($context, $documentPublicId);
        $document = $this->repository->delete(
            $context->organization->id,
            $context->workspace?->id,
            $documentPublicId,
        );

        $this->activityService->log(
            organizationId: $context->organization->id,
            workspaceId: $context->workspace?->id,
            documentPublicId: $documentPublicId,
            action: 'deleted',
            beforeState: $before->toArray(),
            userId: $context->user->id,
            membershipId: $context->membership->id,
        );
        $this->auditRecorder->recordDeleted($document);

        return $document;
    }

    /**
     * @return list<DocumentVersionReference>
     */
    public function listVersions(TenantContext $context, string $documentPublicId): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);
        $this->show($context, $documentPublicId);

        return $this->versionManager->list(
            $context->organization->id,
            $context->workspace?->id,
            $documentPublicId,
        );
    }

    public function createVersion(TenantContext $context, DocumentVersionRequest $request): DocumentVersionReference
    {
        $this->requireCapability($context);
        $this->assertVersion($context);
        $this->quotaService->assertWithinQuota($context->organization, $context->workspace);

        return $this->versionManager->create(
            $context->organization->id,
            $context->workspace?->id,
            $request,
        );
    }

    public function restoreVersion(
        TenantContext $context,
        string $documentPublicId,
        string $versionPublicId,
    ): DocumentVersionReference {
        $this->requireCapability($context);
        $this->assertVersion($context);

        return $this->versionManager->restore(
            $context->organization->id,
            $context->workspace?->id,
            $documentPublicId,
            $versionPublicId,
        );
    }

    public function attach(TenantContext $context, AttachmentRequest $request): AttachmentReference
    {
        $this->requireCapability($context);
        $this->assertAttach($context);

        return $this->attachmentRepository->attach(
            $context->organization->id,
            $context->workspace?->id,
            $request,
        );
    }

    /**
     * @return list<AttachmentReference>
     */
    public function listAttachments(TenantContext $context, string $documentPublicId): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->attachmentRepository->listForDocument(
            $context->organization->id,
            $context->workspace?->id,
            $documentPublicId,
        );
    }

    public function detach(TenantContext $context, string $attachmentPublicId): AttachmentReference
    {
        $this->requireCapability($context);
        $this->assertAttach($context);

        return $this->attachmentRepository->detach(
            $context->organization->id,
            $context->workspace?->id,
            $attachmentPublicId,
        );
    }

    public function requestPreview(
        TenantContext $context,
        string $documentPublicId,
        ?string $versionPublicId = null,
    ): DocumentPreview {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->previewProvider->request(
            $context->organization->id,
            $context->workspace?->id,
            $documentPublicId,
            $versionPublicId,
        );
    }

    public function requestThumbnail(
        TenantContext $context,
        string $documentPublicId,
        ?string $versionPublicId = null,
    ): DocumentThumbnail {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->thumbnailProvider->request(
            $context->organization->id,
            $context->workspace?->id,
            $documentPublicId,
            $versionPublicId,
        );
    }

    public function scan(TenantContext $context, string $documentPublicId): DocumentScanResult
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->scanner->scan(
            $context->organization->id,
            $context->workspace?->id,
            $documentPublicId,
        );
    }

    public function ocr(TenantContext $context, string $documentPublicId): DocumentOcrResult
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->ocrProvider->request(
            $context->organization->id,
            $context->workspace?->id,
            $documentPublicId,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listActivity(TenantContext $context, string $documentPublicId): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->activityService->list(
            $context->organization->id,
            $context->workspace?->id,
            $documentPublicId,
        );
    }

    public function applyRetention(TenantContext $context, DocumentRetentionRule $rule): DocumentRetentionRule
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->retentionPolicy->apply(
            $context->organization->id,
            $context->workspace?->id,
            $rule,
        );
    }

    public function health(TenantContext $context): DocumentHealthReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->healthService->health($context);
    }

    public function statistics(TenantContext $context): DocumentStatistics
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->statisticsService->statisticsForScope(
            $context->organization,
            $context->workspace,
        );
    }

    public function quota(TenantContext $context): DocumentQuotaReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->quotaService->report(
            $context->organization,
            $context->workspace,
        );
    }

    public function createPlaceholder(
        TenantContext $context,
        string $title,
        ?string $moduleKey = null,
        array $metadata = [],
    ): DocumentReference {
        $this->requireCapability($context);
        $this->assertUpload($context);

        if (! $this->repository instanceof EnterpriseDocumentRepositoryService) {
            throw new \RuntimeException('Document repository does not support placeholder creation.');
        }

        $document = $this->repository->createDocumentRecord(
            organizationId: $context->organization->id,
            workspaceId: $context->workspace?->id,
            title: $title,
            description: null,
            visibility: 'organization',
            category: 'export',
            moduleKey: $moduleKey,
            metadata: array_merge($metadata, ['placeholder' => true]),
            createdByUserId: $context->user->id,
            createdByMembershipId: $context->membership->id,
        );

        $reference = EnterpriseDocumentMapper::toReference($document);

        $this->activityService->log(
            organizationId: $context->organization->id,
            workspaceId: $context->workspace?->id,
            documentPublicId: $reference->publicId,
            enterpriseDocumentId: $document->id,
            action: 'placeholder_created',
            afterState: $reference->toArray(),
            userId: $context->user->id,
            membershipId: $context->membership->id,
        );
        $this->auditRecorder->recordUploaded($reference);

        return $reference;
    }

    private function requireCapability(TenantContext $context): void
    {
        $this->runtimeBridge->requireCapability($context, 'documents');
    }

    private function assertRead(TenantContext $context): void
    {
        if (! $this->permissionService->canRead($context)) {
            throw new HttpException(403, 'You do not have permission to read documents.');
        }
    }

    private function assertUpload(TenantContext $context): void
    {
        if (! $this->permissionService->canUpload($context)) {
            throw new HttpException(403, 'You do not have permission to upload documents.');
        }
    }

    private function assertUpdate(TenantContext $context): void
    {
        if (! $this->permissionService->canUpdate($context)) {
            throw new HttpException(403, 'You do not have permission to update documents.');
        }
    }

    private function assertDelete(TenantContext $context): void
    {
        if (! $this->permissionService->canDelete($context)) {
            throw new HttpException(403, 'You do not have permission to delete documents.');
        }
    }

    private function assertVersion(TenantContext $context): void
    {
        if (! $this->permissionService->canVersion($context)) {
            throw new HttpException(403, 'You do not have permission to version documents.');
        }
    }

    private function assertAttach(TenantContext $context): void
    {
        if (! $this->permissionService->canAttach($context)) {
            throw new HttpException(403, 'You do not have permission to attach documents.');
        }
    }

    private function assertManage(TenantContext $context): void
    {
        if (! $this->permissionService->canManage($context)) {
            throw new HttpException(403, 'You do not have permission to manage documents.');
        }
    }
}
