<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardDefinitionResource;
use App\Http\Resources\DashboardRenderResource;
use App\Http\Resources\DashboardViewResource;
use App\Http\Resources\DashboardWidgetResource;
use App\Models\DashboardDefinition;
use App\Modules\Sdk\Dashboard\Data\DashboardView;
use App\Modules\Sdk\Dashboard\Data\DashboardWidget;
use App\Services\Dashboard\DynamicDashboardDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class DynamicDashboardController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DynamicDashboardDevelopmentService $developmentService,
    ) {
    }

    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', DashboardDefinition::class);
        $context = app(TenantContext::class);

        return DashboardDefinitionResource::collection($this->developmentService->listDefinitions($context));
    }

    public function show(string $moduleKey, string $dashboardKey): DashboardDefinitionResource
    {
        $this->authorize('view', DashboardDefinition::class);
        $context = app(TenantContext::class);

        return new DashboardDefinitionResource(
            $this->developmentService->showDefinition($context, $moduleKey, $dashboardKey),
        );
    }

    public function render(Request $request, string $moduleKey, string $dashboardKey): DashboardRenderResource
    {
        $this->authorize('render', DashboardDefinition::class);
        $context = app(TenantContext::class);
        $definition = $this->developmentService->showDefinition($context, $moduleKey, $dashboardKey);

        return new DashboardRenderResource(
            $this->developmentService->renderDashboard(
                $context,
                $definition,
                $request->input('context', []),
            ),
        );
    }

    public function widgets(string $moduleKey, string $dashboardKey): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('view', DashboardDefinition::class);
        $context = app(TenantContext::class);

        return DashboardWidgetResource::collection(
            $this->developmentService->listWidgets($context, $moduleKey, $dashboardKey),
        );
    }

    public function storeWidget(Request $request, string $moduleKey, string $dashboardKey): \Illuminate\Http\JsonResponse
    {
        $this->authorize('manage', DashboardDefinition::class);
        $validated = $request->validate([
            'widget_key' => ['required', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'widget_type' => ['nullable', 'string', 'max:64'],
            'chart_type' => ['nullable', 'string', 'max:32'],
            'data_source_type' => ['nullable', 'string', 'max:64'],
            'data_source_config' => ['nullable', 'array'],
            'metric' => ['nullable', 'array'],
            'filters' => ['nullable', 'array'],
            'layout' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
            'refresh_mode' => ['nullable', 'string', 'max:32'],
            'metadata' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $context = app(TenantContext::class);

        return (new DashboardWidgetResource(
            $this->developmentService->createWidget($context, $moduleKey, $dashboardKey, DashboardWidget::fromArray($validated)),
        ))->response()->setStatusCode(201);
    }

    public function updateWidget(Request $request, string $widgetPublicId): DashboardWidgetResource
    {
        $this->authorize('manage', DashboardDefinition::class);
        $validated = $request->validate([
            'widget_key' => ['nullable', 'string', 'max:128'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'widget_type' => ['nullable', 'string', 'max:64'],
            'chart_type' => ['nullable', 'string', 'max:32'],
            'data_source_type' => ['nullable', 'string', 'max:64'],
            'data_source_config' => ['nullable', 'array'],
            'metric' => ['nullable', 'array'],
            'filters' => ['nullable', 'array'],
            'layout' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
            'refresh_mode' => ['nullable', 'string', 'max:32'],
            'metadata' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $context = app(TenantContext::class);
        $payload = array_merge($validated, ['public_id' => $widgetPublicId]);

        return new DashboardWidgetResource(
            $this->developmentService->updateWidget($context, DashboardWidget::fromArray($payload)),
        );
    }

    public function destroyWidget(string $widgetPublicId): \Illuminate\Http\Response
    {
        $this->authorize('manage', DashboardDefinition::class);
        $context = app(TenantContext::class);

        $this->developmentService->deleteWidgetByPublicId($context, $widgetPublicId);

        return response()->noContent();
    }

    public function views(string $moduleKey, string $dashboardKey): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('view', DashboardDefinition::class);
        $context = app(TenantContext::class);

        return DashboardViewResource::collection(
            $this->developmentService->listViews($context, $moduleKey, $dashboardKey),
        );
    }

    public function storeView(Request $request, string $moduleKey, string $dashboardKey): \Illuminate\Http\JsonResponse
    {
        $this->authorize('manage', DashboardDefinition::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'layout' => ['nullable', 'array'],
            'filters' => ['nullable', 'array'],
            'is_default' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ]);

        $context = app(TenantContext::class);

        return (new DashboardViewResource(
            $this->developmentService->saveView($context, $moduleKey, $dashboardKey, DashboardView::fromArray([
                'name' => $validated['name'],
                'layout' => $validated['layout'] ?? null,
                'filters' => $validated['filters'] ?? [],
                'is_default' => $validated['is_default'] ?? false,
                'metadata' => $validated['metadata'] ?? [],
            ])),
        ))->response()->setStatusCode(201);
    }

    public function setDefaultView(string $viewPublicId): DashboardViewResource
    {
        $this->authorize('manage', DashboardDefinition::class);
        $context = app(TenantContext::class);

        return new DashboardViewResource(
            $this->developmentService->setDefaultView($context, $viewPublicId),
        );
    }

    public function destroyView(string $viewPublicId): \Illuminate\Http\Response
    {
        $this->authorize('manage', DashboardDefinition::class);
        $context = app(TenantContext::class);

        $this->developmentService->deleteViewByPublicId($context, $viewPublicId);

        return response()->noContent();
    }
}
