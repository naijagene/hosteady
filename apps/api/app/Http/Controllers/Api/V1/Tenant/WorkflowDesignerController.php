<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkflowCanvasSnapshotResource;
use App\Http\Resources\WorkflowDesignerDiffResource;
use App\Http\Resources\WorkflowDesignerPreviewResource;
use App\Http\Resources\WorkflowCloneResultResource;
use App\Http\Resources\WorkflowExportResultResource;
use App\Http\Resources\WorkflowImportResultResource;
use App\Http\Resources\WorkflowNodeTemplateResource;
use App\Models\WorkflowCanvasSnapshot;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvas;
use App\Services\Enterprise\Workflow\Designer\WorkflowDesignerService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class WorkflowDesignerController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly WorkflowDesignerService $designerService,
    ) {
    }

    public function preview(string $definitionPublicId): WorkflowDesignerPreviewResource
    {
        $this->authorize('viewAny', WorkflowCanvasSnapshot::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new WorkflowDesignerPreviewResource(
            $this->designerService->preview($context, $definitionPublicId),
        );
    }

    public function showCanvas(string $definitionPublicId): WorkflowCanvasSnapshotResource
    {
        $this->authorize('viewAny', WorkflowCanvasSnapshot::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new WorkflowCanvasSnapshotResource(
            $this->designerService->getCanvas($context, $definitionPublicId),
        );
    }

    public function storeCanvas(Request $request, string $definitionPublicId): \Illuminate\Http\JsonResponse
    {
        $this->authorize('manage', WorkflowCanvasSnapshot::class);

        $validated = $request->validate([
            'nodes' => ['nullable', 'array'],
            'edges' => ['nullable', 'array'],
            'viewport' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return (new WorkflowCanvasSnapshotResource(
            $this->designerService->saveCanvas(
                $context,
                $definitionPublicId,
                WorkflowCanvas::fromArray($validated),
            ),
        ))->response()->setStatusCode(201);
    }

    public function indexSnapshots(Request $request, string $definitionPublicId): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkflowCanvasSnapshot::class);

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return WorkflowCanvasSnapshotResource::collection(
            $this->designerService->listSnapshots($context, $definitionPublicId, $validated['limit'] ?? 50),
        );
    }

    public function showSnapshot(string $snapshotPublicId): WorkflowCanvasSnapshotResource
    {
        $this->authorize('viewAny', WorkflowCanvasSnapshot::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new WorkflowCanvasSnapshotResource(
            $this->designerService->getSnapshot($context, $snapshotPublicId),
        );
    }

    public function diffSnapshots(string $fromPublicId, string $toPublicId): WorkflowDesignerDiffResource
    {
        $this->authorize('viewAny', WorkflowCanvasSnapshot::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new WorkflowDesignerDiffResource(
            $this->designerService->diffSnapshots($context, $fromPublicId, $toPublicId),
        );
    }

    public function clone(Request $request, string $definitionPublicId): \Illuminate\Http\JsonResponse
    {
        $this->authorize('manage', WorkflowCanvasSnapshot::class);

        $validated = $request->validate([
            'workflow_key' => ['nullable', 'string', 'max:128'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return (new WorkflowCloneResultResource(
            $this->designerService->cloneWorkflow($context, $definitionPublicId, $validated),
        ))->response()->setStatusCode(201);
    }

    public function export(string $definitionPublicId): WorkflowExportResultResource
    {
        $this->authorize('export', WorkflowCanvasSnapshot::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new WorkflowExportResultResource(
            $this->designerService->exportWorkflow($context, $definitionPublicId),
        );
    }

    public function import(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('import', WorkflowCanvasSnapshot::class);

        $validated = $request->validate([
            'format' => ['nullable', 'string', 'in:heos_json'],
            'workflow' => ['nullable', 'array'],
            'canvas' => ['nullable', 'array'],
            'nodes' => ['nullable', 'array'],
            'transitions' => ['nullable', 'array'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return (new WorkflowImportResultResource(
            $this->designerService->importWorkflow($context, $validated),
        ))->response()->setStatusCode(201);
    }

    public function indexNodeTemplates(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkflowCanvasSnapshot::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return WorkflowNodeTemplateResource::collection(
            $this->designerService->listNodeTemplates($context),
        );
    }
}
