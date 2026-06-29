<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\NavigationDefinitionResource;
use App\Http\Resources\NavigationHealthResource;
use App\Http\Resources\NavigationItemResource;
use App\Http\Resources\NavigationPersonalizationResource;
use App\Http\Resources\NavigationRenderResource;
use App\Http\Resources\NavigationRuntimeResource;
use App\Http\Resources\NavigationStatisticsResource;
use App\Http\Resources\NavigationTreeResource;
use App\Http\Resources\NavigationVersionResource;
use App\Models\NavigationDefinition;
use App\Modules\Sdk\Navigation\Data\NavigationDefinition as NavigationDefinitionDto;
use App\Modules\Sdk\Navigation\Data\NavigationItem as NavigationItemDto;
use App\Services\Navigation\NavigationDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnterpriseNavigationDesignerController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly NavigationDevelopmentService $developmentService) {}

    public function indexDefinitions(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', NavigationDefinition::class);

        return NavigationDefinitionResource::collection(
            $this->developmentService->listDefinitions(app(TenantContext::class)),
        );
    }

    public function storeDefinition(Request $request): JsonResponse
    {
        $this->authorize('create', NavigationDefinition::class);
        $validated = $request->validate([
            'module_key' => ['nullable', 'string', 'max:64'],
            'navigation_key' => ['required', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'visibility' => ['nullable', 'string'],
            'scope' => ['nullable', 'string'],
            'structure' => ['nullable', 'array'],
            'conditions' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'application_public_id' => ['nullable', 'string'],
        ]);
        $created = $this->developmentService->registerDefinition(
            app(TenantContext::class),
            NavigationDefinitionDto::fromArray($validated),
        );

        return (new NavigationDefinitionResource($created))->response()->setStatusCode(201);
    }

    public function showDefinition(string $definitionPublicId): NavigationDefinitionResource
    {
        $this->authorize('view', NavigationDefinition::class);

        return new NavigationDefinitionResource(
            $this->developmentService->findDefinitionByPublicId(app(TenantContext::class), $definitionPublicId),
        );
    }

    public function updateDefinition(Request $request, string $definitionPublicId): NavigationDefinitionResource
    {
        $this->authorize('update', NavigationDefinition::class);
        $validated = $request->validate([
            'module_key' => ['sometimes', 'nullable', 'string', 'max:64'],
            'navigation_key' => ['sometimes', 'string', 'max:128'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'type' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string'],
            'visibility' => ['sometimes', 'string'],
            'scope' => ['sometimes', 'string'],
            'structure' => ['sometimes', 'array'],
            'conditions' => ['sometimes', 'array'],
            'metadata' => ['sometimes', 'array'],
            'application_public_id' => ['sometimes', 'nullable', 'string'],
        ]);

        return new NavigationDefinitionResource(
            $this->developmentService->updateDefinitionByPublicId(
                app(TenantContext::class),
                $definitionPublicId,
                $validated,
            ),
        );
    }

    public function indexItems(string $definitionPublicId): AnonymousResourceCollection
    {
        $this->authorize('view', NavigationDefinition::class);

        return NavigationItemResource::collection(
            $this->developmentService->listItems(app(TenantContext::class), $definitionPublicId),
        );
    }

    public function storeItem(Request $request, string $definitionPublicId): JsonResponse
    {
        $this->authorize('create', NavigationDefinition::class);
        $validated = $request->validate([
            'item_key' => ['required', 'string', 'max:128'],
            'label' => ['required', 'string', 'max:255'],
            'item_type' => ['nullable', 'string'],
            'parent_item_public_id' => ['nullable', 'string'],
            'module_key' => ['nullable', 'string', 'max:64'],
            'route' => ['nullable', 'string', 'max:512'],
            'icon' => ['nullable', 'string'],
            'badge' => ['nullable', 'array'],
            'visibility' => ['nullable', 'string'],
            'conditions' => ['nullable', 'array'],
            'permissions' => ['nullable', 'array'],
            'roles' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
            'application_public_id' => ['nullable', 'string'],
        ]);
        $created = $this->developmentService->createItemForDefinition(
            app(TenantContext::class),
            $definitionPublicId,
            NavigationItemDto::fromArray($validated),
        );

        return (new NavigationItemResource($created))->response()->setStatusCode(201);
    }

    public function updateItem(Request $request, string $itemPublicId): NavigationItemResource
    {
        $this->authorize('update', NavigationDefinition::class);
        $validated = $request->validate([
            'item_key' => ['sometimes', 'string', 'max:128'],
            'label' => ['sometimes', 'string', 'max:255'],
            'item_type' => ['sometimes', 'string'],
            'parent_item_public_id' => ['sometimes', 'nullable', 'string'],
            'module_key' => ['sometimes', 'nullable', 'string', 'max:64'],
            'route' => ['sometimes', 'nullable', 'string', 'max:512'],
            'icon' => ['sometimes', 'nullable', 'string'],
            'badge' => ['sometimes', 'array'],
            'visibility' => ['sometimes', 'string'],
            'conditions' => ['sometimes', 'array'],
            'permissions' => ['sometimes', 'array'],
            'roles' => ['sometimes', 'array'],
            'sort_order' => ['sometimes', 'integer'],
            'metadata' => ['sometimes', 'array'],
            'application_public_id' => ['sometimes', 'nullable', 'string'],
        ]);

        return new NavigationItemResource(
            $this->developmentService->updateItem(app(TenantContext::class), $itemPublicId, $validated),
        );
    }

    public function destroyItem(string $itemPublicId): JsonResponse
    {
        $this->authorize('delete', NavigationDefinition::class);
        $this->developmentService->deleteItem(app(TenantContext::class), $itemPublicId);

        return response()->json(null, 204);
    }

    public function showTree(Request $request, string $definitionPublicId): NavigationTreeResource
    {
        $this->authorize('view', NavigationDefinition::class);
        $previewDraft = $request->boolean('preview_draft');

        return new NavigationTreeResource(
            $this->developmentService->buildTree(app(TenantContext::class), $definitionPublicId, $previewDraft),
        );
    }

    public function renderDefinition(Request $request, string $definitionPublicId): NavigationRenderResource
    {
        $this->authorize('view', NavigationDefinition::class);
        $previewDraft = $request->boolean('preview_draft');

        return new NavigationRenderResource(
            $this->developmentService->renderDefinition(app(TenantContext::class), $definitionPublicId, $previewDraft),
        );
    }

    public function storeVersion(Request $request, string $definitionPublicId): JsonResponse
    {
        $this->authorize('create', NavigationDefinition::class);
        $validated = $request->validate([
            'structure' => ['nullable', 'array'],
        ]);
        $created = $this->developmentService->createVersion(
            app(TenantContext::class),
            $definitionPublicId,
            $validated['structure'] ?? [],
        );

        return (new NavigationVersionResource($created))->response()->setStatusCode(201);
    }

    public function indexVersions(string $definitionPublicId): AnonymousResourceCollection
    {
        $this->authorize('view', NavigationDefinition::class);

        return NavigationVersionResource::collection(
            $this->developmentService->listVersionsForDefinition(app(TenantContext::class), $definitionPublicId),
        );
    }

    public function publishDefinition(Request $request, string $definitionPublicId): NavigationDefinitionResource
    {
        $this->authorize('publish', NavigationDefinition::class);
        $validated = $request->validate([
            'version_public_id' => ['nullable', 'string'],
        ]);

        return new NavigationDefinitionResource(
            $this->developmentService->publishDefinition(
                app(TenantContext::class),
                $definitionPublicId,
                $validated['version_public_id'] ?? null,
            ),
        );
    }

    public function updatePersonalization(Request $request, string $definitionPublicId): NavigationPersonalizationResource
    {
        $this->authorize('personalize', NavigationDefinition::class);
        $validated = $request->validate(['personalization' => ['nullable', 'array']]);

        return new NavigationPersonalizationResource(
            $this->developmentService->updatePersonalization(
                app(TenantContext::class),
                $definitionPublicId,
                $validated['personalization'] ?? [],
            ),
        );
    }

    public function health(): NavigationHealthResource
    {
        $this->authorize('viewAny', NavigationDefinition::class);

        return new NavigationHealthResource($this->developmentService->health(app(TenantContext::class)));
    }

    public function runtime(Request $request): NavigationRuntimeResource
    {
        $this->authorize('view', NavigationDefinition::class);
        $validated = $request->validate([
            'navigation_key' => ['nullable', 'string', 'max:128'],
            'module_key' => ['nullable', 'string', 'max:64'],
        ]);

        return new NavigationRuntimeResource(
            $this->developmentService->composeRuntime(
                app(TenantContext::class),
                $validated['navigation_key'] ?? 'main',
                $validated['module_key'] ?? null,
            ),
        );
    }

    public function statistics(): NavigationStatisticsResource
    {
        $this->authorize('viewAny', NavigationDefinition::class);

        return new NavigationStatisticsResource($this->developmentService->statistics(app(TenantContext::class)));
    }
}
