<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkflowExecutionResultResource;
use App\Http\Resources\WorkflowInstanceResource;
use App\Models\WorkflowInstance;
use App\Services\Enterprise\Workflow\Runtime\WorkflowRuntimeService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class WorkflowInstanceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly WorkflowRuntimeService $workflowRuntimeService,
    ) {
    }

    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkflowInstance::class);

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,running,waiting,completed,failed,cancelled'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return WorkflowInstanceResource::collection(
            $this->workflowRuntimeService->list($context, $validated['status'] ?? null),
        );
    }

    public function show(string $publicId): WorkflowInstanceResource
    {
        $this->authorize('view', WorkflowInstance::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new WorkflowInstanceResource(
            $this->workflowRuntimeService->show($context, $publicId),
        );
    }

    public function cancel(string $publicId): WorkflowInstanceResource
    {
        $this->authorize('execute', WorkflowInstance::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new WorkflowInstanceResource(
            $this->workflowRuntimeService->cancel($context, $publicId),
        );
    }

    public function resume(string $publicId): WorkflowExecutionResultResource
    {
        $this->authorize('execute', WorkflowInstance::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new WorkflowExecutionResultResource(
            $this->workflowRuntimeService->resume($context, $publicId),
        );
    }

    public function history(string $publicId): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', WorkflowInstance::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return response()->json([
            'data' => $this->workflowRuntimeService->history($context, $publicId),
        ]);
    }
}
