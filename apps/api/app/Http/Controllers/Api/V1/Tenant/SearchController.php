<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\SavedSearchResource;
use App\Http\Resources\SearchResultResource;
use App\Models\PlatformSearchIndex;
use App\Modules\Sdk\Enterprise\Data\SearchRequest;
use App\Services\Enterprise\Search\SearchService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SearchService $searchService,
    ) {
    }

    public function index(Request $request): SearchResultResource
    {
        $this->authorize('viewAny', PlatformSearchIndex::class);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:512'],
            'module_key' => ['nullable', 'string', 'max:64'],
            'entity_type' => ['nullable', 'string', 'max:128'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new SearchResultResource(
            $this->searchService->search($context, new SearchRequest(
                scope: new \App\Modules\Sdk\Enterprise\Data\EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: $validated['module_key'] ?? null,
                ),
                query: $validated['q'] ?? null,
                moduleKey: $validated['module_key'] ?? null,
                entityType: $validated['entity_type'] ?? null,
                limit: (int) ($validated['limit'] ?? 25),
            )),
        );
    }

    public function suggestions(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', PlatformSearchIndex::class);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:512'],
            'module_key' => ['nullable', 'string', 'max:64'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return response()->json([
            'data' => $this->searchService->suggestions(
                $context,
                $validated['q'] ?? null,
                $validated['module_key'] ?? null,
            ),
        ]);
    }

    public function recent(): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', PlatformSearchIndex::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return response()->json([
            'data' => $this->searchService->recent($context),
        ]);
    }

    public function storeSaved(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', PlatformSearchIndex::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'query' => ['nullable', 'string', 'max:512'],
            'module_key' => ['nullable', 'string', 'max:64'],
            'filters' => ['nullable', 'array'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return (new SavedSearchResource(
            $this->searchService->saveSearch(
                $context,
                $validated['name'],
                $validated['query'] ?? null,
                $validated['filters'] ?? null,
                $validated['module_key'] ?? null,
            ),
        ))->response()->setStatusCode(201);
    }

    public function listSaved(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', PlatformSearchIndex::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return SavedSearchResource::collection(
            $this->searchService->listSavedSearches($context),
        );
    }

    public function destroySaved(string $savedSearchPublicId): \Illuminate\Http\JsonResponse
    {
        $this->authorize('deleteSaved', PlatformSearchIndex::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $this->searchService->deleteSavedSearch($context, $savedSearchPublicId);

        return response()->json(['message' => 'Saved search deleted.']);
    }
}
