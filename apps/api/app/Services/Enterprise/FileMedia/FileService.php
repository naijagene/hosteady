<?php

namespace App\Services\Enterprise\FileMedia;

use App\Modules\Sdk\Enterprise\Contracts\FileServicePort;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\FileDownloadResult;
use App\Modules\Sdk\Enterprise\Data\FileReference;
use App\Modules\Sdk\Enterprise\Data\FileUpdateRequest;
use App\Modules\Sdk\Enterprise\Data\FileUploadRequest;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;

class FileService
{
    public function __construct(
        private readonly FileServicePort $fileServicePort,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
        private readonly FileQueryService $fileQueryService,
        private readonly FileVisibilityResolver $visibilityResolver,
    ) {
    }

    public function upload(TenantContext $context, FileUploadRequest $request): FileReference
    {
        $this->runtimeBridge->requireCapability($context, 'storage');

        return $this->fileServicePort->upload(new FileUploadRequest(
            scope: $this->scopeFromContext($context, $request->scope->moduleKey),
            originalFilename: $request->originalFilename,
            mimeType: $request->mimeType,
            sizeBytes: $request->sizeBytes,
            contents: $request->contents,
            visibility: $request->visibility,
            entityReference: $request->entityReference,
            displayName: $request->displayName,
            metadata: $request->metadata,
            uploadedMembershipPublicId: $context->membershipPublicId,
        ));
    }

    public function update(TenantContext $context, FileUpdateRequest $request): FileReference
    {
        $this->runtimeBridge->requireCapability($context, 'storage');

        $file = $this->fileQueryService->findModel(
            $this->scopeFromContext($context, $request->scope->moduleKey),
            $request->filePublicId,
        );

        if (! $this->visibilityResolver->canManage($file, $context, $this->hasManagePermission($context))) {
            $this->recordAccessDenied($context, $file->public_id);
            abort(403, 'You are not allowed to update this file.');
        }

        return $this->fileServicePort->update(new FileUpdateRequest(
            scope: $this->scopeFromContext($context, $request->scope->moduleKey),
            filePublicId: $request->filePublicId,
            displayName: $request->displayName,
            visibility: $request->visibility,
            entityReference: $request->entityReference,
            metadata: $request->metadata,
        ));
    }

    public function delete(TenantContext $context, string $filePublicId): void
    {
        $this->runtimeBridge->requireCapability($context, 'storage');

        $file = $this->fileQueryService->findModel(
            $this->scopeFromContext($context),
            $filePublicId,
        );

        if (! $this->visibilityResolver->canManage($file, $context, $this->hasManagePermission($context))) {
            $this->recordAccessDenied($context, $filePublicId);
            abort(403, 'You are not allowed to delete this file.');
        }

        $this->fileServicePort->delete($this->scopeFromContext($context), $filePublicId);
    }

    public function find(TenantContext $context, string $filePublicId): ?FileReference
    {
        $this->runtimeBridge->requireCapability($context, 'media');

        $file = $this->fileQueryService->findModel($this->scopeFromContext($context), $filePublicId);

        if (! $this->visibilityResolver->canAccess($file, $context, $this->hasReadPermission($context))) {
            $this->recordAccessDenied($context, $filePublicId);
            abort(403, 'You are not allowed to access this file.');
        }

        return $this->fileServicePort->find($this->scopeFromContext($context), $filePublicId);
    }

    /**
     * @return list<FileReference>
     */
    public function list(TenantContext $context, ?string $moduleKey = null, int $limit = 50): array
    {
        $this->runtimeBridge->requireCapability($context, 'media');
        $this->assertReadPermission($context);

        return $this->fileServicePort->listForScope(
            $this->scopeFromContext($context, $moduleKey),
            $limit,
        );
    }

    /**
     * @return list<FileReference>
     */
    public function listForEntity(TenantContext $context, EntityReference $entityReference): array
    {
        $this->runtimeBridge->requireCapability($context, 'media');
        $this->assertReadPermission($context);

        return $this->fileServicePort->listForEntity(
            $this->scopeFromContext($context, $entityReference->moduleKey),
            $entityReference,
        );
    }

    public function download(TenantContext $context, string $filePublicId): FileDownloadResult
    {
        $this->runtimeBridge->requireCapability($context, 'media');

        $file = $this->fileQueryService->findModel($this->scopeFromContext($context), $filePublicId);

        if (! $this->visibilityResolver->canAccess($file, $context, $this->hasReadPermission($context))) {
            $this->recordAccessDenied($context, $filePublicId);
            abort(403, 'You are not allowed to download this file.');
        }

        return $this->fileServicePort->download($this->scopeFromContext($context), $filePublicId);
    }

    private function scopeFromContext(TenantContext $context, ?string $moduleKey = null): EnterpriseScope
    {
        return new EnterpriseScope(
            organizationPublicId: $context->organizationPublicId,
            workspacePublicId: $context->workspacePublicId,
            moduleKey: $moduleKey,
        );
    }

    private function hasReadPermission(TenantContext $context): bool
    {
        return app(\App\Services\Authorization\TenantAuthorizationService::class)
            ->allows($context, 'files.read');
    }

    private function hasManagePermission(TenantContext $context): bool
    {
        return app(\App\Services\Authorization\TenantAuthorizationService::class)
            ->allows($context, 'files.manage');
    }

    private function assertReadPermission(TenantContext $context): void
    {
        if (! $this->hasReadPermission($context)) {
            abort(403, 'You are not allowed to read files.');
        }
    }

    private function recordAccessDenied(TenantContext $context, string $filePublicId): void
    {
        app(\App\Services\Enterprise\Audit\EnterpriseFileAuditRecorder::class)
            ->recordAccessDenied($context, $filePublicId);
    }
}
