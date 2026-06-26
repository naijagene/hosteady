<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlatformFileResource;
use App\Models\PlatformFile;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\FileUpdateRequest;
use App\Modules\Sdk\Enterprise\Data\FileUploadRequest;
use App\Services\Enterprise\FileMedia\FileService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformFileController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly FileService $fileService,
    ) {
    }

    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', PlatformFile::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return PlatformFileResource::collection(
            $this->fileService->list($context, moduleKey: request()->query('module_key')),
        );
    }

    public function show(string $filePublicId): PlatformFileResource
    {
        $this->authorize('viewAny', PlatformFile::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $file = $this->fileService->find($context, $filePublicId);

        abort_if($file === null, 404);

        return new PlatformFileResource($file);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', PlatformFile::class);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'visibility' => ['nullable', 'string', 'in:private,workspace,organization,public'],
            'module_key' => ['nullable', 'string', 'max:64'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'entity_type' => ['nullable', 'string', 'max:128'],
            'entity_public_id' => ['nullable', 'string', 'max:128'],
            'entity_label' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $uploaded = $request->file('file');
        abort_if($uploaded === null, 422, 'File is required.');

        $entityReference = null;

        if (! empty($validated['entity_type']) && ! empty($validated['entity_public_id'])) {
            $entityReference = new EntityReference(
                type: $validated['entity_type'],
                publicId: $validated['entity_public_id'],
                moduleKey: $validated['module_key'] ?? null,
                label: $validated['entity_label'] ?? null,
            );
        }

        $file = $this->fileService->upload($context, new FileUploadRequest(
            scope: new EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
                moduleKey: $validated['module_key'] ?? null,
            ),
            originalFilename: $uploaded->getClientOriginalName(),
            mimeType: (string) ($uploaded->getMimeType() ?? 'application/octet-stream'),
            sizeBytes: (int) $uploaded->getSize(),
            contents: $uploaded->get(),
            visibility: $validated['visibility'] ?? 'private',
            entityReference: $entityReference,
            displayName: $validated['display_name'] ?? null,
            metadata: $validated['metadata'] ?? [],
        ));

        return (new PlatformFileResource($file))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, string $filePublicId): PlatformFileResource
    {
        $this->authorize('update', PlatformFile::class);

        $validated = $request->validate([
            'display_name' => ['nullable', 'string', 'max:255'],
            'visibility' => ['nullable', 'string', 'in:private,workspace,organization,public'],
            'entity_type' => ['nullable', 'string', 'max:128'],
            'entity_public_id' => ['nullable', 'string', 'max:128'],
            'entity_label' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'module_key' => ['nullable', 'string', 'max:64'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $entityReference = null;

        if (! empty($validated['entity_type']) && ! empty($validated['entity_public_id'])) {
            $entityReference = new EntityReference(
                type: $validated['entity_type'],
                publicId: $validated['entity_public_id'],
                moduleKey: $validated['module_key'] ?? null,
                label: $validated['entity_label'] ?? null,
            );
        }

        $file = $this->fileService->update($context, new FileUpdateRequest(
            scope: new EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
                moduleKey: $validated['module_key'] ?? null,
            ),
            filePublicId: $filePublicId,
            displayName: $validated['display_name'] ?? null,
            visibility: $validated['visibility'] ?? null,
            entityReference: $entityReference,
            metadata: $validated['metadata'] ?? null,
        ));

        return new PlatformFileResource($file);
    }

    public function destroy(string $filePublicId): \Illuminate\Http\JsonResponse
    {
        $this->authorize('delete', PlatformFile::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $this->fileService->delete($context, $filePublicId);

        return response()->json(['message' => 'File deleted.']);
    }

    public function entity(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', PlatformFile::class);

        $validated = $request->validate([
            'entity_type' => ['required', 'string', 'max:128'],
            'entity_public_id' => ['required', 'string', 'max:128'],
            'module_key' => ['nullable', 'string', 'max:64'],
            'entity_label' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return PlatformFileResource::collection(
            $this->fileService->listForEntity($context, new EntityReference(
                type: $validated['entity_type'],
                publicId: $validated['entity_public_id'],
                moduleKey: $validated['module_key'] ?? null,
                label: $validated['entity_label'] ?? null,
            )),
        );
    }

    public function download(string $filePublicId): StreamedResponse
    {
        $this->authorize('viewAny', PlatformFile::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $download = $this->fileService->download($context, $filePublicId);

        return response()->streamDownload(
            function () use ($download) {
                echo $download->contents;
            },
            $download->file->originalFilename,
            [
                'Content-Type' => $download->file->mimeType,
            ],
        );
    }
}
