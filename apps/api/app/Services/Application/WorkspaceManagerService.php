<?php

namespace App\Services\Application;

use App\Models\ApplicationRuntime\ApplicationRuntimeApp;
use App\Models\ApplicationRuntime\ApplicationWorkspace as ApplicationWorkspaceModel;
use App\Modules\Sdk\Application\Contracts\WorkspaceProvider;
use App\Modules\Sdk\Application\Data\ApplicationWorkspace;
use App\Modules\Sdk\Application\Enums\WorkspaceStatus;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class WorkspaceManagerService implements WorkspaceProvider
{
    public function __construct(
        private readonly ApplicationAuditRecorder $auditRecorder,
    ) {
    }

    /** @return list<ApplicationWorkspace> */
    public function workspaces(TenantContext $context): array
    {
        $query = ApplicationWorkspaceModel::query()->with('application')->orderBy('name');
        ApplicationRuntimeMapper::applyOrganizationScope($query, $context->organization->id);
        ApplicationRuntimeMapper::applyWorkspaceScope($query, $context->workspace?->id);

        return $query->get()->map(fn (ApplicationWorkspaceModel $model) => ApplicationRuntimeMapper::toWorkspace($model))->all();
    }

    public function createForApplication(
        TenantContext $context,
        ApplicationRuntimeApp $application,
        string $workspaceKey,
        string $name,
    ): ApplicationWorkspace {
        $model = ApplicationWorkspaceModel::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace?->id,
            'application_runtime_app_id' => $application->id,
            'workspace_key' => $workspaceKey,
            'name' => $name,
            'status' => WorkspaceStatus::Active->value,
            'metadata' => [],
        ]);

        $workspace = ApplicationRuntimeMapper::toWorkspace($model);
        $this->auditRecorder->recordWorkspaceCreated($workspace);

        return $workspace;
    }
}
