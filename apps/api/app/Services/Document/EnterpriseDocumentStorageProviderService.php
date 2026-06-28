<?php

namespace App\Services\Document;

use App\Modules\Sdk\Document\Contracts\DocumentStorageProvider;
use App\Modules\Sdk\Document\Data\DocumentUploadRequest;
use App\Modules\Sdk\Document\Data\DocumentVersionRequest;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\FileUploadRequest;
use App\Services\Enterprise\FileMedia\FileService;
use App\Support\Tenant\TenantContext;

class EnterpriseDocumentStorageProviderService implements DocumentStorageProvider
{
    public function __construct(
        private readonly FileService $fileService,
    ) {
    }

    public function storeUpload(TenantContext $context, DocumentUploadRequest $request): string
    {
        $file = $this->fileService->upload($context, new FileUploadRequest(
            scope: new EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
                moduleKey: $request->moduleKey,
            ),
            originalFilename: $request->originalFilename,
            mimeType: $request->mimeType,
            sizeBytes: $request->sizeBytes,
            contents: $request->contents,
            visibility: $request->visibility,
            displayName: $request->title,
            metadata: array_merge($request->metadata, [
                'document_category' => $request->category,
            ]),
        ));

        return $file->publicId;
    }

    public function storeVersion(TenantContext $context, DocumentVersionRequest $request): string
    {
        $file = $this->fileService->upload($context, new FileUploadRequest(
            scope: new EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
            ),
            originalFilename: $request->originalFilename,
            mimeType: $request->mimeType,
            sizeBytes: $request->sizeBytes,
            contents: $request->contents,
            visibility: 'organization',
            displayName: $request->label ?? $request->originalFilename,
            metadata: $request->metadata,
        ));

        return $file->publicId;
    }
}
