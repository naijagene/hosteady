<?php

namespace App\Services\WorkspaceApplication;

use App\Enums\OrganizationApplicationStatus;
use App\Models\Application;
use App\Models\Organization;
use App\Models\OrganizationApplication;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Application\ApplicationInstallationService;
use App\Support\Tenant\TenantContext;

class WorkspaceApplicationBootstrapService
{
    /**
     * @var list<string>
     */
    private const BOOTSTRAP_APPLICATION_KEYS = ['core', 'workspace'];

    public function __construct(
        private readonly ApplicationInstallationService $applicationInstallationService,
        private readonly WorkspaceApplicationService $workspaceApplicationService,
    ) {
    }

    public function bootstrapDefaultWorkspace(
        Organization $organization,
        Workspace $workspace,
        OrganizationMembership $membership,
        User $actor,
        int $actorUserId,
    ): void {
        if (! $workspace->is_default) {
            return;
        }

        $context = TenantContext::fromModels($actor, $organization, $membership, $workspace);

        foreach (self::BOOTSTRAP_APPLICATION_KEYS as $applicationKey) {
            $application = Application::query()
                ->where('key', $applicationKey)
                ->first();

            if ($application === null) {
                continue;
            }

            $organizationApplication = OrganizationApplication::query()
                ->where('organization_id', $organization->id)
                ->where('application_id', $application->id)
                ->where('status', '!=', OrganizationApplicationStatus::Uninstalled)
                ->whereNull('deleted_at')
                ->first();

            if ($organizationApplication === null) {
                $organizationApplication = $this->applicationInstallationService->install(
                    $context,
                    $application->public_id,
                );
            }

            try {
                $this->workspaceApplicationService->enable(
                    $context,
                    $organizationApplication->public_id,
                    isBootstrap: true,
                );
            } catch (\App\Exceptions\WorkspaceApplication\DuplicateWorkspaceApplicationException) {
                continue;
            }
        }
    }
}
