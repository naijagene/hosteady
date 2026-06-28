<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\UiComponentResource;
use App\Http\Resources\UiHealthResource;
use App\Http\Resources\UiLayoutResource;
use App\Http\Resources\UiPageResource;
use App\Http\Resources\UiPersonalizationResource;
use App\Http\Resources\UiRenderResource;
use App\Http\Resources\UiStatisticsResource;
use App\Models\UiPage;
use App\Modules\Sdk\Ui\Data\UiComponent;
use App\Modules\Sdk\Ui\Data\UiLayout;
use App\Modules\Sdk\Ui\Data\UiPageDefinition;
use App\Services\Ui\UiDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnterpriseUiController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly UiDevelopmentService $developmentService) {}

    public function indexPages(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', UiPage::class);
        return UiPageResource::collection($this->developmentService->listPages(app(TenantContext::class)));
    }

    public function storePage(Request $request): JsonResponse
    {
        $this->authorize('create', UiPage::class);
        $validated = $request->validate([
            'module_key' => ['nullable', 'string', 'max:64'],
            'page_key' => ['required', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'page_type' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'visibility' => ['nullable', 'string'],
            'route_path' => ['nullable', 'string', 'max:512'],
            'icon' => ['nullable', 'string'],
            'layout' => ['nullable', 'array'],
            'regions' => ['nullable', 'array'],
            'components' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
            'conditions' => ['nullable', 'array'],
            'breakpoints' => ['nullable', 'array'],
            'theme' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);
        $created = $this->developmentService->registerPage(app(TenantContext::class), UiPageDefinition::fromArray($validated));
        return (new UiPageResource($created))->response()->setStatusCode(201);
    }

    public function showPage(string $moduleKey, string $pageKey): UiPageResource
    {
        $this->authorize('view', UiPage::class);
        return new UiPageResource($this->developmentService->findPage(app(TenantContext::class), $moduleKey, $pageKey));
    }

    public function renderPage(string $moduleKey, string $pageKey): UiRenderResource
    {
        $this->authorize('render', UiPage::class);
        return new UiRenderResource($this->developmentService->renderPage(app(TenantContext::class), $moduleKey, $pageKey));
    }

    public function indexLayouts(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', UiPage::class);
        return UiLayoutResource::collection($this->developmentService->listLayouts(app(TenantContext::class)));
    }

    public function storeLayout(Request $request): JsonResponse
    {
        $this->authorize('create', UiPage::class);
        $validated = $request->validate([
            'layout_key' => ['required', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'layout_type' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'module_key' => ['nullable', 'string'],
            'regions' => ['nullable', 'array'],
            'breakpoints' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);
        $created = $this->developmentService->registerLayout(app(TenantContext::class), UiLayout::fromArray($validated));
        return (new UiLayoutResource($created))->response()->setStatusCode(201);
    }

    public function indexComponents(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', UiPage::class);
        return UiComponentResource::collection($this->developmentService->listComponents(app(TenantContext::class)));
    }

    public function storeComponent(Request $request): JsonResponse
    {
        $this->authorize('create', UiPage::class);
        $validated = $request->validate([
            'component_key' => ['required', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'component_type' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'module_key' => ['nullable', 'string'],
            'binding_type' => ['nullable', 'string'],
            'binding_config' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
            'conditions' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);
        $created = $this->developmentService->registerComponent(app(TenantContext::class), UiComponent::fromArray($validated));
        return (new UiComponentResource($created))->response()->setStatusCode(201);
    }

    public function runtime(): UiRenderResource
    {
        $this->authorize('render', UiPage::class);
        return new UiRenderResource($this->developmentService->composeRuntime(app(TenantContext::class)));
    }

    public function health(): UiHealthResource
    {
        $this->authorize('viewAny', UiPage::class);
        return new UiHealthResource($this->developmentService->health(app(TenantContext::class)));
    }

    public function statistics(): UiStatisticsResource
    {
        $this->authorize('viewAny', UiPage::class);
        return new UiStatisticsResource($this->developmentService->statistics(app(TenantContext::class)));
    }

    public function updatePersonalization(Request $request, string $pagePublicId): UiPersonalizationResource
    {
        $this->authorize('personalize', UiPage::class);
        $validated = $request->validate(['personalization' => ['nullable', 'array']]);
        return new UiPersonalizationResource(
            $this->developmentService->updatePersonalization(
                app(TenantContext::class),
                $pagePublicId,
                $validated['personalization'] ?? [],
            ),
        );
    }
}
