<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkflowCategoryResource;
use App\Models\WorkflowDefinition;
use App\Services\Enterprise\Workflow\WorkflowCategoryService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class WorkflowCategoryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly WorkflowCategoryService $workflowCategoryService,
    ) {
    }

    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkflowDefinition::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return WorkflowCategoryResource::collection(
            $this->workflowCategoryService->list($context),
        );
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', WorkflowDefinition::class);

        $validated = $request->validate([
            'category_key' => ['required', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'module_key' => ['nullable', 'string', 'max:64'],
            'metadata' => ['nullable', 'array'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return (new WorkflowCategoryResource(
            $this->workflowCategoryService->create(
                $context,
                $validated['category_key'],
                $validated['name'],
                $validated['description'] ?? null,
                $validated['module_key'] ?? null,
                $validated['metadata'] ?? null,
            ),
        ))->response()->setStatusCode(201);
    }
}
