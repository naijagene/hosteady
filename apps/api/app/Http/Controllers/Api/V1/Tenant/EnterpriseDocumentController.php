<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\DocumentActivityResource;
use App\Http\Resources\DocumentHealthResource;
use App\Http\Resources\DocumentOcrResource;
use App\Http\Resources\DocumentPreviewResource;
use App\Http\Resources\DocumentResource;
use App\Http\Resources\DocumentScanResource;
use App\Http\Resources\DocumentStatisticsResource;
use App\Http\Resources\DocumentThumbnailResource;
use App\Http\Resources\DocumentVersionResource;
use App\Models\EnterpriseDocument;
use App\Modules\Sdk\Document\Data\AttachmentRequest;
use App\Modules\Sdk\Document\Data\DocumentUpdateRequest;
use App\Modules\Sdk\Document\Data\DocumentUploadRequest;
use App\Modules\Sdk\Document\Data\DocumentVersionRequest;
use App\Services\Document\EnterpriseDocumentDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class EnterpriseDocumentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly EnterpriseDocumentDevelopmentService $developmentService,
    ) {
    }

    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', EnterpriseDocument::class);
        $context = app(TenantContext::class);

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return DocumentResource::collection(
            $this->developmentService->list($context, (int) ($validated['limit'] ?? 50)),
        );
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', EnterpriseDocument::class);
        $context = app(TenantContext::class);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['nullable', 'string', 'in:private,workspace,organization,public'],
            'category' => ['nullable', 'string', 'max:64'],
            'module_key' => ['nullable', 'string', 'max:64'],
            'metadata' => ['nullable', 'array'],
        ]);

        $uploaded = $request->file('file');
        abort_if($uploaded === null, 422, 'File is required.');

        $document = $this->developmentService->upload($context, new DocumentUploadRequest(
            title: $validated['title'] ?? $uploaded->getClientOriginalName(),
            originalFilename: $uploaded->getClientOriginalName(),
            mimeType: (string) ($uploaded->getMimeType() ?? 'application/octet-stream'),
            sizeBytes: (int) $uploaded->getSize(),
            contents: $uploaded->get(),
            description: $validated['description'] ?? null,
            visibility: $validated['visibility'] ?? 'organization',
            category: $validated['category'] ?? 'general',
            moduleKey: $validated['module_key'] ?? null,
            metadata: $validated['metadata'] ?? [],
        ));

        return (new DocumentResource($document))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $documentPublicId): DocumentResource
    {
        $this->authorize('view', EnterpriseDocument::class);
        $context = app(TenantContext::class);

        return new DocumentResource(
            $this->developmentService->show($context, $documentPublicId),
        );
    }

    public function update(Request $request, string $documentPublicId): DocumentResource
    {
        $this->authorize('update', EnterpriseDocument::class);
        $context = app(TenantContext::class);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'string', 'max:32'],
            'visibility' => ['nullable', 'string', 'in:private,workspace,organization,public'],
            'category' => ['nullable', 'string', 'max:64'],
            'metadata' => ['nullable', 'array'],
        ]);

        return new DocumentResource(
            $this->developmentService->update($context, DocumentUpdateRequest::fromArray(array_merge($validated, [
                'document_public_id' => $documentPublicId,
            ]))),
        );
    }

    public function destroy(string $documentPublicId): DocumentResource
    {
        $this->authorize('delete', EnterpriseDocument::class);
        $context = app(TenantContext::class);

        return new DocumentResource(
            $this->developmentService->delete($context, $documentPublicId),
        );
    }

    public function versions(string $documentPublicId): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('view', EnterpriseDocument::class);
        $context = app(TenantContext::class);

        return DocumentVersionResource::collection(
            $this->developmentService->listVersions($context, $documentPublicId),
        );
    }

    public function storeVersion(Request $request, string $documentPublicId): \Illuminate\Http\JsonResponse
    {
        $this->authorize('version', EnterpriseDocument::class);
        $context = app(TenantContext::class);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'label' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);

        $uploaded = $request->file('file');
        abort_if($uploaded === null, 422, 'File is required.');

        $version = $this->developmentService->createVersion($context, new DocumentVersionRequest(
            documentPublicId: $documentPublicId,
            originalFilename: $uploaded->getClientOriginalName(),
            mimeType: (string) ($uploaded->getMimeType() ?? 'application/octet-stream'),
            sizeBytes: (int) $uploaded->getSize(),
            contents: $uploaded->get(),
            label: $validated['label'] ?? null,
            metadata: $validated['metadata'] ?? [],
        ));

        return (new DocumentVersionResource($version))
            ->response()
            ->setStatusCode(201);
    }

    public function restoreVersion(
        string $documentPublicId,
        string $versionPublicId,
    ): DocumentVersionResource {
        $this->authorize('version', EnterpriseDocument::class);
        $context = app(TenantContext::class);

        return new DocumentVersionResource(
            $this->developmentService->restoreVersion($context, $documentPublicId, $versionPublicId),
        );
    }

    public function attachments(string $documentPublicId): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('view', EnterpriseDocument::class);
        $context = app(TenantContext::class);

        return AttachmentResource::collection(
            $this->developmentService->listAttachments($context, $documentPublicId),
        );
    }

    public function storeAttachment(Request $request, string $documentPublicId): \Illuminate\Http\JsonResponse
    {
        $this->authorize('attach', EnterpriseDocument::class);
        $context = app(TenantContext::class);

        $validated = $request->validate([
            'subject_type' => ['required', 'string', 'max:64'],
            'subject_public_id' => ['required', 'string', 'max:128'],
            'subject_module_key' => ['nullable', 'string', 'max:64'],
            'subject_entity_key' => ['nullable', 'string', 'max:64'],
            'metadata' => ['nullable', 'array'],
        ]);

        $attachment = $this->developmentService->attach($context, AttachmentRequest::fromArray(array_merge($validated, [
            'document_public_id' => $documentPublicId,
        ])));

        return (new AttachmentResource($attachment))
            ->response()
            ->setStatusCode(201);
    }

    public function destroyAttachment(string $attachmentPublicId): AttachmentResource
    {
        $this->authorize('attach', EnterpriseDocument::class);
        $context = app(TenantContext::class);

        return new AttachmentResource(
            $this->developmentService->detach($context, $attachmentPublicId),
        );
    }

    public function preview(Request $request, string $documentPublicId): DocumentPreviewResource
    {
        $this->authorize('view', EnterpriseDocument::class);
        $context = app(TenantContext::class);

        $validated = $request->validate([
            'version_public_id' => ['nullable', 'string', 'max:128'],
        ]);

        return new DocumentPreviewResource(
            $this->developmentService->requestPreview(
                $context,
                $documentPublicId,
                $validated['version_public_id'] ?? null,
            ),
        );
    }

    public function thumbnail(Request $request, string $documentPublicId): DocumentThumbnailResource
    {
        $this->authorize('view', EnterpriseDocument::class);
        $context = app(TenantContext::class);

        $validated = $request->validate([
            'version_public_id' => ['nullable', 'string', 'max:128'],
        ]);

        return new DocumentThumbnailResource(
            $this->developmentService->requestThumbnail(
                $context,
                $documentPublicId,
                $validated['version_public_id'] ?? null,
            ),
        );
    }

    public function scan(string $documentPublicId): DocumentScanResource
    {
        $this->authorize('view', EnterpriseDocument::class);
        $context = app(TenantContext::class);

        return new DocumentScanResource(
            $this->developmentService->scan($context, $documentPublicId),
        );
    }

    public function ocr(string $documentPublicId): DocumentOcrResource
    {
        $this->authorize('view', EnterpriseDocument::class);
        $context = app(TenantContext::class);

        return new DocumentOcrResource(
            $this->developmentService->ocr($context, $documentPublicId),
        );
    }

    public function activity(string $documentPublicId): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('view', EnterpriseDocument::class);
        $context = app(TenantContext::class);

        return DocumentActivityResource::collection(
            $this->developmentService->listActivity($context, $documentPublicId),
        );
    }

    public function health(): DocumentHealthResource
    {
        $this->authorize('viewAny', EnterpriseDocument::class);
        $context = app(TenantContext::class);

        return new DocumentHealthResource(
            $this->developmentService->health($context),
        );
    }

    public function statistics(): DocumentStatisticsResource
    {
        $this->authorize('viewAny', EnterpriseDocument::class);
        $context = app(TenantContext::class);

        return new DocumentStatisticsResource(
            $this->developmentService->statistics($context),
        );
    }
}
