<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScheduledTaskResource;
use App\Http\Resources\ScheduledTaskRunResource;
use App\Models\ScheduledTask;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\ScheduledTaskRequest;
use App\Services\Enterprise\Scheduler\SchedulerService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ScheduledTaskController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SchedulerService $schedulerService,
    ) {
    }

    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', ScheduledTask::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return ScheduledTaskResource::collection(
            $this->schedulerService->list($context),
        );
    }

    public function show(string $taskPublicId): ScheduledTaskResource
    {
        $this->authorize('viewAny', ScheduledTask::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $task = $this->schedulerService->find($context, $taskPublicId);

        abort_if($task === null, 404);

        return new ScheduledTaskResource($task);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', ScheduledTask::class);

        $validated = $request->validate([
            'task_type' => ['required', 'string', 'max:128'],
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'module_key' => ['nullable', 'string', 'max:64'],
            'cron_expression' => ['nullable', 'string', 'max:128'],
            'run_at' => ['nullable', 'date'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'payload' => ['nullable', 'array'],
            'entity_type' => ['nullable', 'string', 'max:128'],
            'entity_public_id' => ['nullable', 'string', 'max:128'],
            'entity_label' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $entityReference = null;

        if (! empty($validated['entity_type']) && ! empty($validated['entity_public_id'])) {
            $entityReference = new \App\Modules\Sdk\Enterprise\Data\EntityReference(
                type: $validated['entity_type'],
                publicId: $validated['entity_public_id'],
                moduleKey: $validated['module_key'] ?? null,
                label: $validated['entity_label'] ?? null,
            );
        }

        $task = $this->schedulerService->create($context, new ScheduledTaskRequest(
            scope: new EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
                moduleKey: $validated['module_key'] ?? null,
            ),
            taskType: $validated['task_type'],
            displayName: $validated['display_name'],
            description: $validated['description'] ?? null,
            cronExpression: $validated['cron_expression'] ?? null,
            runAt: $validated['run_at'] ?? null,
            timezone: $validated['timezone'] ?? null,
            payload: $validated['payload'] ?? [],
            entityReference: $entityReference,
        ));

        return (new ScheduledTaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    public function pause(string $taskPublicId): ScheduledTaskResource
    {
        $this->authorize('update', ScheduledTask::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new ScheduledTaskResource(
            $this->schedulerService->pause($context, $taskPublicId),
        );
    }

    public function resume(string $taskPublicId): ScheduledTaskResource
    {
        $this->authorize('update', ScheduledTask::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new ScheduledTaskResource(
            $this->schedulerService->resume($context, $taskPublicId),
        );
    }

    public function destroy(string $taskPublicId): \Illuminate\Http\JsonResponse
    {
        $this->authorize('delete', ScheduledTask::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $this->schedulerService->cancel($context, $taskPublicId);

        return response()->json(['message' => 'Scheduled task cancelled.']);
    }

    public function runs(string $taskPublicId): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', ScheduledTask::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return ScheduledTaskRunResource::collection(
            $this->schedulerService->listRuns($context, $taskPublicId),
        );
    }
}
