<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\HumanTaskResource;
use App\Http\Resources\TaskCommentResource;
use App\Models\WorkflowHumanTask;
use App\Services\Enterprise\Workflow\Human\HumanTaskService;
use App\Services\Enterprise\Workflow\Human\TaskInboxService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class HumanTaskController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly HumanTaskService $humanTaskService,
        private readonly TaskInboxService $taskInboxService,
    ) {
    }

    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkflowHumanTask::class);

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:created,assigned,opened,in_progress,completed,rejected,cancelled'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return HumanTaskResource::collection(
            $this->humanTaskService->list($context, $validated['status'] ?? null),
        );
    }

    public function show(string $publicId): HumanTaskResource
    {
        $this->authorize('view', WorkflowHumanTask::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new HumanTaskResource(
            $this->humanTaskService->show($context, $publicId),
        );
    }

    public function open(string $publicId): HumanTaskResource
    {
        $this->authorize('manage', WorkflowHumanTask::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new HumanTaskResource(
            $this->humanTaskService->open($context, $publicId),
        );
    }

    public function complete(Request $request, string $publicId): HumanTaskResource
    {
        $this->authorize('manage', WorkflowHumanTask::class);

        $validated = $request->validate([
            'result' => ['nullable', 'array'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new HumanTaskResource(
            $this->humanTaskService->complete($context, $publicId, $validated['result'] ?? null),
        );
    }

    public function cancel(string $publicId): HumanTaskResource
    {
        $this->authorize('manage', WorkflowHumanTask::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new HumanTaskResource(
            $this->humanTaskService->cancel($context, $publicId),
        );
    }

    public function addComment(Request $request, string $publicId): TaskCommentResource
    {
        $this->authorize('view', WorkflowHumanTask::class);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new TaskCommentResource(
            $this->humanTaskService->addComment($context, $publicId, $validated['body']),
        );
    }

    public function comments(string $publicId): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('view', WorkflowHumanTask::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return TaskCommentResource::collection(
            $this->humanTaskService->listComments($context, $publicId),
        );
    }

    public function history(string $publicId): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', WorkflowHumanTask::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return response()->json([
            'data' => $this->humanTaskService->history($context, $publicId),
        ]);
    }

    public function statistics(): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', WorkflowHumanTask::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return response()->json([
            'data' => $this->humanTaskService->statistics($context)->toArray(),
        ]);
    }

    public function inbox(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkflowHumanTask::class);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:assigned,pending,approvals,overdue,all'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return HumanTaskResource::collection(
            $this->taskInboxService->inbox(
                $context,
                $validated['type'],
                $validated['limit'] ?? 50,
            ),
        );
    }
}
