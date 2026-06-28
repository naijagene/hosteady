<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\EntityRecordActivityResource;
use App\Http\Resources\EntityRecordLinkResource;
use App\Http\Resources\EntityRecordMutationResultResource;
use App\Http\Resources\EntityRecordQueryResultResource;
use App\Http\Resources\EntityRecordResource;
use App\Http\Resources\EntityRecordVersionResource;
use App\Models\EnterpriseEntityRecord;
use App\Modules\Sdk\DataRepository\Data\EntityRecordCreateRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordDeleteRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordQueryRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordRestoreRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordUpdateRequest;
use App\Services\DataRepository\EnterpriseEntityRecordDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class EnterpriseEntityRecordController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly EnterpriseEntityRecordDevelopmentService $developmentService,
    ) {
    }

    public function index(Request $request, string $moduleKey, string $entityKey): EntityRecordQueryResultResource
    {
        $this->authorize('query', EnterpriseEntityRecord::class);
        $context = app(TenantContext::class);

        $validated = $request->validate([
            'filters' => ['nullable', 'array'],
            'sorts' => ['nullable', 'array'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'search' => ['nullable', 'string'],
            'include_deleted' => ['nullable', 'boolean'],
        ]);

        return new EntityRecordQueryResultResource(
            $this->developmentService->query($context, EntityRecordQueryRequest::fromArray(array_merge($validated, [
                'module_key' => $moduleKey,
                'entity_key' => $entityKey,
            ]))),
        );
    }

    public function store(Request $request, string $moduleKey, string $entityKey): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', EnterpriseEntityRecord::class);
        $validated = $request->validate([
            'values' => ['required', 'array'],
            'visibility' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);
        $context = app(TenantContext::class);

        return (new EntityRecordMutationResultResource(
            $this->developmentService->create($context, EntityRecordCreateRequest::fromArray(array_merge($validated, [
                'module_key' => $moduleKey,
                'entity_key' => $entityKey,
            ]))),
        ))->response()->setStatusCode(201);
    }

    public function show(string $moduleKey, string $entityKey, string $recordPublicId): EntityRecordResource
    {
        $this->authorize('view', EnterpriseEntityRecord::class);
        $context = app(TenantContext::class);

        return new EntityRecordResource(
            $this->developmentService->show($context, $moduleKey, $entityKey, $recordPublicId),
        );
    }

    public function update(Request $request, string $moduleKey, string $entityKey, string $recordPublicId): EntityRecordMutationResultResource
    {
        $this->authorize('update', EnterpriseEntityRecord::class);
        $validated = $request->validate([
            'values' => ['required', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);
        $context = app(TenantContext::class);

        return new EntityRecordMutationResultResource(
            $this->developmentService->update($context, EntityRecordUpdateRequest::fromArray(array_merge($validated, [
                'module_key' => $moduleKey,
                'entity_key' => $entityKey,
                'record_public_id' => $recordPublicId,
            ]))),
        );
    }

    public function destroy(string $moduleKey, string $entityKey, string $recordPublicId): EntityRecordMutationResultResource
    {
        $this->authorize('delete', EnterpriseEntityRecord::class);
        $context = app(TenantContext::class);

        return new EntityRecordMutationResultResource(
            $this->developmentService->delete($context, EntityRecordDeleteRequest::fromArray([
                'module_key' => $moduleKey,
                'entity_key' => $entityKey,
                'record_public_id' => $recordPublicId,
            ])),
        );
    }

    public function restore(string $moduleKey, string $entityKey, string $recordPublicId): EntityRecordMutationResultResource
    {
        $this->authorize('restore', EnterpriseEntityRecord::class);
        $context = app(TenantContext::class);

        return new EntityRecordMutationResultResource(
            $this->developmentService->restore($context, EntityRecordRestoreRequest::fromArray([
                'module_key' => $moduleKey,
                'entity_key' => $entityKey,
                'record_public_id' => $recordPublicId,
            ])),
        );
    }

    public function versions(string $moduleKey, string $entityKey, string $recordPublicId): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('view', EnterpriseEntityRecord::class);
        $context = app(TenantContext::class);

        return EntityRecordVersionResource::collection(
            $this->developmentService->listVersions($context, $moduleKey, $entityKey, $recordPublicId),
        );
    }

    public function activity(string $moduleKey, string $entityKey, string $recordPublicId): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('view', EnterpriseEntityRecord::class);
        $context = app(TenantContext::class);

        return EntityRecordActivityResource::collection(
            $this->developmentService->listActivity($context, $moduleKey, $entityKey, $recordPublicId),
        );
    }

    public function links(string $moduleKey, string $entityKey, string $recordPublicId): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('view', EnterpriseEntityRecord::class);
        $context = app(TenantContext::class);

        return EntityRecordLinkResource::collection(
            $this->developmentService->listLinks($context, $moduleKey, $entityKey, $recordPublicId),
        );
    }

    public function storeLink(
        Request $request,
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
    ): \Illuminate\Http\JsonResponse {
        $this->authorize('link', EnterpriseEntityRecord::class);
        $validated = $request->validate([
            'target_module_key' => ['required', 'string', 'max:128'],
            'target_entity_key' => ['required', 'string', 'max:128'],
            'target_record_public_id' => ['required', 'uuid'],
            'relationship_key' => ['nullable', 'string', 'max:128'],
            'metadata' => ['nullable', 'array'],
        ]);
        $context = app(TenantContext::class);

        return (new EntityRecordLinkResource(
            $this->developmentService->createLink($context, $moduleKey, $entityKey, $recordPublicId, $validated),
        ))->response()->setStatusCode(201);
    }

    public function destroyLink(string $linkPublicId): \Illuminate\Http\Response
    {
        $this->authorize('link', EnterpriseEntityRecord::class);
        $context = app(TenantContext::class);

        $this->developmentService->deleteLink($context, $linkPublicId);

        return response()->noContent();
    }
}
