<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkspaceApplication\EnableWorkspaceApplicationRequest;
use App\Http\Resources\AvailableWorkspaceApplicationResource;
use App\Http\Resources\WorkspaceApplicationResource;
use App\Models\WorkspaceApplication;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WorkspaceApplicationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly WorkspaceApplicationService $workspaceApplicationService,
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkspaceApplication::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return WorkspaceApplicationResource::collection(
            $this->workspaceApplicationService->listForWorkspace($context),
        );
    }

    public function available(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkspaceApplication::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return AvailableWorkspaceApplicationResource::collection(
            $this->workspaceApplicationService->listAvailable($context),
        );
    }

    public function store(EnableWorkspaceApplicationRequest $request): JsonResponse
    {
        $this->authorize('enable', WorkspaceApplication::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $workspaceApplication = $this->workspaceApplicationService->enable(
            $context,
            $request->string('organization_application_public_id')->value(),
        );

        return (new WorkspaceApplicationResource($workspaceApplication))
            ->response()
            ->setStatusCode(201);
    }

    public function enable(string $workspaceApplicationPublicId): WorkspaceApplicationResource
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $workspaceApplication = $this->workspaceApplicationService->resolveWorkspaceApplication(
            $context,
            $workspaceApplicationPublicId,
        );

        $this->authorize('enable', $workspaceApplication);

        return new WorkspaceApplicationResource(
            $this->workspaceApplicationService->reEnable($context, $workspaceApplicationPublicId),
        );
    }

    public function disable(string $workspaceApplicationPublicId): WorkspaceApplicationResource
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $workspaceApplication = $this->workspaceApplicationService->resolveWorkspaceApplication(
            $context,
            $workspaceApplicationPublicId,
        );

        $this->authorize('manage', $workspaceApplication);

        return new WorkspaceApplicationResource(
            $this->workspaceApplicationService->disable($context, $workspaceApplicationPublicId),
        );
    }

    public function archive(string $workspaceApplicationPublicId): WorkspaceApplicationResource
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $workspaceApplication = $this->workspaceApplicationService->resolveWorkspaceApplication(
            $context,
            $workspaceApplicationPublicId,
        );

        $this->authorize('manage', $workspaceApplication);

        return new WorkspaceApplicationResource(
            $this->workspaceApplicationService->archive($context, $workspaceApplicationPublicId),
        );
    }

    public function destroy(string $workspaceApplicationPublicId): Response
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $workspaceApplication = $this->workspaceApplicationService->resolveWorkspaceApplication(
            $context,
            $workspaceApplicationPublicId,
        );

        $this->authorize('manage', $workspaceApplication);

        $this->workspaceApplicationService->remove($context, $workspaceApplicationPublicId);

        return response()->noContent();
    }
}
