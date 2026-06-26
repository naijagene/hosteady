<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlatformJobResource;
use App\Models\PlatformJob;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\PlatformJobDispatchRequest;
use App\Services\Enterprise\Jobs\PlatformJobService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class PlatformJobController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PlatformJobService $platformJobService,
    ) {
    }

    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', PlatformJob::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return PlatformJobResource::collection(
            $this->platformJobService->list($context, moduleKey: request()->query('module_key')),
        );
    }

    public function show(string $jobPublicId): PlatformJobResource
    {
        $this->authorize('viewAny', PlatformJob::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $job = $this->platformJobService->find($context, $jobPublicId);

        abort_if($job === null, 404);

        return new PlatformJobResource($job);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', PlatformJob::class);

        $validated = $request->validate([
            'job_type' => ['required', 'string', 'max:128'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'module_key' => ['nullable', 'string', 'max:64'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,critical'],
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

        $result = $this->platformJobService->dispatch($context, new PlatformJobDispatchRequest(
            scope: new EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
                moduleKey: $validated['module_key'] ?? null,
            ),
            jobType: $validated['job_type'],
            displayName: $validated['display_name'] ?? null,
            payload: $validated['payload'] ?? [],
            entityReference: $entityReference,
            priority: $validated['priority'] ?? 'normal',
        ));

        return (new PlatformJobResource($result->job))
            ->response()
            ->setStatusCode(201);
    }

    public function cancel(string $jobPublicId): PlatformJobResource
    {
        $this->authorize('cancel', PlatformJob::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new PlatformJobResource(
            $this->platformJobService->cancel($context, $jobPublicId),
        );
    }
}
