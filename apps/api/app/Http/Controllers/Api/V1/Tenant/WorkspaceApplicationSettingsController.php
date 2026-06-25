<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkspaceApplication\IndexWorkspaceApplicationSettingsRequest;
use App\Http\Requests\WorkspaceApplication\ResetWorkspaceApplicationSettingsRequest;
use App\Http\Requests\WorkspaceApplication\UpdateWorkspaceApplicationSettingsRequest;
use App\Http\Requests\WorkspaceApplication\WorkspaceApplicationSettingsHistoryRequest;
use App\Http\Resources\WorkspaceApplicationSettingHistoryResource;
use App\Http\Resources\WorkspaceApplicationSettingResource;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Services\WorkspaceApplication\WorkspaceSettingsService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkspaceApplicationSettingsController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly WorkspaceApplicationService $workspaceApplicationService,
        private readonly WorkspaceSettingsService $workspaceSettingsService,
    ) {
    }

    public function index(IndexWorkspaceApplicationSettingsRequest $request): AnonymousResourceCollection
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $workspaceApplication = $this->workspaceApplicationService->resolveWorkspaceApplication(
            $context,
            $request->string('workspace_application_public_id')->value(),
        );

        $this->authorize('view', $workspaceApplication);

        return WorkspaceApplicationSettingResource::collection(
            $this->workspaceSettingsService->listSettings(
                $context,
                $workspaceApplication->public_id,
            ),
        );
    }

    public function update(UpdateWorkspaceApplicationSettingsRequest $request): AnonymousResourceCollection
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $workspaceApplicationPublicId = $request->string('workspace_application_public_id')->value();

        $workspaceApplication = $this->workspaceApplicationService->resolveWorkspaceApplication(
            $context,
            $workspaceApplicationPublicId,
        );

        $this->authorize('configure', $workspaceApplication);

        return WorkspaceApplicationSettingResource::collection(
            $this->workspaceSettingsService->bulkUpdate(
                $context,
                $workspaceApplicationPublicId,
                $request->validated('settings'),
                $request->validated('reason'),
            ),
        );
    }

    public function reset(ResetWorkspaceApplicationSettingsRequest $request): AnonymousResourceCollection
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $workspaceApplicationPublicId = $request->string('workspace_application_public_id')->value();

        $workspaceApplication = $this->workspaceApplicationService->resolveWorkspaceApplication(
            $context,
            $workspaceApplicationPublicId,
        );

        $this->authorize('configure', $workspaceApplication);

        $keys = $request->validated('keys');

        return WorkspaceApplicationSettingResource::collection(
            $this->workspaceSettingsService->reset(
                $context,
                $workspaceApplicationPublicId,
                $keys,
                $request->validated('reason'),
            ),
        );
    }

    public function history(WorkspaceApplicationSettingsHistoryRequest $request): AnonymousResourceCollection
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $workspaceApplication = $this->workspaceApplicationService->resolveWorkspaceApplication(
            $context,
            $request->string('workspace_application_public_id')->value(),
        );

        $this->authorize('view', $workspaceApplication);

        $paginator = $this->workspaceSettingsService->history(
            $context,
            $workspaceApplication->public_id,
            $request->validated('setting_key'),
            (int) ($request->validated('page') ?? 1),
            (int) ($request->validated('per_page') ?? 25),
        );

        return WorkspaceApplicationSettingHistoryResource::collection($paginator);
    }
}
