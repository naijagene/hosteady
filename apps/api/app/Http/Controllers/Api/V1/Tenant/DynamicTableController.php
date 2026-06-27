<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\TableDefinitionResource;
use App\Http\Resources\TableQueryResultResource;
use App\Http\Resources\TableRenderResource;
use App\Http\Resources\TableViewResource;
use App\Models\TableDefinition;
use App\Modules\Sdk\Table\Data\TableQueryRequest;
use App\Modules\Sdk\Table\Data\TableView;
use App\Services\Table\DynamicTableDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class DynamicTableController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DynamicTableDevelopmentService $developmentService,
    ) {
    }

    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', TableDefinition::class);
        $context = app(TenantContext::class);

        return TableDefinitionResource::collection($this->developmentService->listDefinitions($context));
    }

    public function show(string $moduleKey, string $tableKey): TableDefinitionResource
    {
        $this->authorize('view', TableDefinition::class);
        $context = app(TenantContext::class);

        return new TableDefinitionResource(
            $this->developmentService->showDefinition($context, $moduleKey, $tableKey),
        );
    }

    public function render(Request $request, string $moduleKey, string $tableKey): TableRenderResource
    {
        $this->authorize('view', TableDefinition::class);
        $context = app(TenantContext::class);
        $definition = $this->developmentService->showDefinition($context, $moduleKey, $tableKey);

        return new TableRenderResource(
            $this->developmentService->renderTable(
                $context,
                $definition,
                $request->input('context', []),
            ),
        );
    }

    public function query(Request $request, string $moduleKey, string $tableKey): TableQueryResultResource
    {
        $this->authorize('query', TableDefinition::class);
        $validated = $request->validate([
            'filters' => ['nullable', 'array'],
            'sorts' => ['nullable', 'array'],
            'search' => ['nullable', 'string', 'max:255'],
            'columns' => ['nullable', 'array'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'metadata' => ['nullable', 'array'],
        ]);

        $context = app(TenantContext::class);

        return new TableQueryResultResource(
            $this->developmentService->queryTable($context, TableQueryRequest::fromArray([
                'module_key' => $moduleKey,
                'table_key' => $tableKey,
                'filters' => $validated['filters'] ?? [],
                'sorts' => $validated['sorts'] ?? [],
                'search' => $validated['search'] ?? null,
                'columns' => $validated['columns'] ?? [],
                'page' => $validated['page'] ?? 1,
                'per_page' => $validated['per_page'] ?? 25,
                'metadata' => $validated['metadata'] ?? [],
            ])),
        );
    }

    public function views(string $moduleKey, string $tableKey): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('view', TableDefinition::class);
        $context = app(TenantContext::class);

        return TableViewResource::collection(
            $this->developmentService->listViews($context, $moduleKey, $tableKey),
        );
    }

    public function storeView(Request $request, string $moduleKey, string $tableKey): \Illuminate\Http\JsonResponse
    {
        $this->authorize('manage', TableDefinition::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'columns' => ['nullable', 'array'],
            'filters' => ['nullable', 'array'],
            'sorts' => ['nullable', 'array'],
            'is_default' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ]);

        $context = app(TenantContext::class);

        return (new TableViewResource(
            $this->developmentService->saveView($context, TableView::fromArray([
                'module_key' => $moduleKey,
                'table_key' => $tableKey,
                'name' => $validated['name'],
                'columns' => $validated['columns'] ?? [],
                'filters' => $validated['filters'] ?? [],
                'sorts' => $validated['sorts'] ?? [],
                'is_default' => $validated['is_default'] ?? false,
                'metadata' => $validated['metadata'] ?? [],
            ])),
        ))->response()->setStatusCode(201);
    }

    public function destroyView(string $viewPublicId): \Illuminate\Http\Response
    {
        $this->authorize('manage', TableDefinition::class);
        $context = app(TenantContext::class);

        $this->developmentService->deleteViewByPublicId($context, $viewPublicId);

        return response()->noContent();
    }
}
