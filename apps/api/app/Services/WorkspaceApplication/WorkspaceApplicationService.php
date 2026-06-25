<?php

namespace App\Services\WorkspaceApplication;

use App\Enums\ApplicationStatus;
use App\Enums\OrganizationApplicationStatus;
use App\Enums\WorkspaceApplicationStatus;
use App\Exceptions\WorkspaceApplication\CoreWorkspaceApplicationProtectedException;
use App\Exceptions\WorkspaceApplication\DuplicateWorkspaceApplicationException;
use App\Exceptions\WorkspaceApplication\InvalidWorkspaceApplicationTransitionException;
use App\Exceptions\WorkspaceApplication\OrganizationEntitlementRequiredException;
use App\Exceptions\WorkspaceApplication\WorkspaceApplicationNotFoundException;
use App\Models\OrganizationApplication;
use App\Models\WorkspaceApplication;
use App\Services\Runtime\RuntimeCacheInvalidator;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkspaceApplicationService
{
    public function __construct(
        private readonly RuntimeCacheInvalidator $runtimeCacheInvalidator,
        private readonly \App\Services\Module\ModuleLifecycleManager $moduleLifecycleManager,
    ) {
    }

    /**
     * @return Collection<int, WorkspaceApplication>
     */
    public function listForWorkspace(TenantContext $context): Collection
    {
        return WorkspaceApplication::query()
            ->with(['application', 'organizationApplication', 'enabledByMembership'])
            ->where('workspace_id', $context->workspace->id)
            ->where('organization_id', $context->organization->id)
            ->where('status', '!=', WorkspaceApplicationStatus::Removed)
            ->whereNull('deleted_at')
            ->orderBy('enabled_at')
            ->get();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listAvailable(TenantContext $context): Collection
    {
        $enabledOrganizationApplicationIds = WorkspaceApplication::query()
            ->where('workspace_id', $context->workspace->id)
            ->where('organization_id', $context->organization->id)
            ->where('status', '!=', WorkspaceApplicationStatus::Removed)
            ->whereNull('deleted_at')
            ->pluck('organization_application_id');

        return OrganizationApplication::query()
            ->with('application')
            ->where('organization_id', $context->organization->id)
            ->where('status', OrganizationApplicationStatus::Active)
            ->whereNull('deleted_at')
            ->whereHas('application', fn ($query) => $query->where('status', ApplicationStatus::Active))
            ->whereNotIn('id', $enabledOrganizationApplicationIds)
            ->orderBy('installed_at')
            ->get()
            ->map(fn (OrganizationApplication $installation) => [
                'organization_application_public_id' => $installation->public_id,
                'status' => $installation->status->value,
                'installed_version' => $installation->installed_version,
                'already_enabled' => false,
                'application' => $installation->application,
            ]);
    }

    public function listActiveForRuntime(TenantContext $context): Collection
    {
        return WorkspaceApplication::query()
            ->with(['application', 'organizationApplication'])
            ->where('workspace_id', $context->workspace->id)
            ->where('organization_id', $context->organization->id)
            ->where('status', WorkspaceApplicationStatus::Active)
            ->whereNull('deleted_at')
            ->whereHas('organizationApplication', fn ($query) => $query
                ->where('status', OrganizationApplicationStatus::Active)
                ->whereNull('deleted_at'))
            ->whereHas('application', fn ($query) => $query->where('status', ApplicationStatus::Active))
            ->orderBy('enabled_at')
            ->get();
    }

    public function countActiveApplications(TenantContext $context): int
    {
        return $this->listActiveForRuntime($context)->count();
    }

    public function enable(
        TenantContext $context,
        string $organizationApplicationPublicId,
        bool $isBootstrap = false,
    ): WorkspaceApplication {
        $organizationApplication = OrganizationApplication::query()
            ->with('application')
            ->where('public_id', $organizationApplicationPublicId)
            ->where('organization_id', $context->organization->id)
            ->whereNull('deleted_at')
            ->first();

        if ($organizationApplication === null) {
            throw new OrganizationEntitlementRequiredException;
        }

        $this->assertOrganizationEntitlementActive($organizationApplication);

        if ($context->workspace->organization_id !== $context->organization->id) {
            throw new WorkspaceApplicationNotFoundException;
        }

        $existing = WorkspaceApplication::query()
            ->with(['application', 'organizationApplication', 'enabledByMembership'])
            ->where('workspace_id', $context->workspace->id)
            ->where('organization_application_id', $organizationApplication->id)
            ->whereNull('deleted_at')
            ->first();

        if ($existing !== null) {
            if ($existing->status === WorkspaceApplicationStatus::Active) {
                throw new DuplicateWorkspaceApplicationException;
            }

            if (in_array($existing->status, [WorkspaceApplicationStatus::Disabled, WorkspaceApplicationStatus::Archived], true)) {
                return $this->reactivateExisting($existing, $context);
            }

            if ($existing->status === WorkspaceApplicationStatus::Enabling) {
                throw new DuplicateWorkspaceApplicationException;
            }
        }

        $workspaceApplication = DB::transaction(function () use ($context, $organizationApplication, $isBootstrap) {
            $workspaceApplication = new WorkspaceApplication([
                'organization_id' => $context->organization->id,
                'workspace_id' => $context->workspace->id,
                'organization_application_id' => $organizationApplication->id,
                'application_id' => $organizationApplication->application_id,
                'status' => WorkspaceApplicationStatus::Enabling,
                'enabled_version' => $organizationApplication->installed_version,
                'is_bootstrap' => $isBootstrap,
                'enabled_at' => null,
                'enabled_by_user_id' => $context->user->id,
                'enabled_by_membership_id' => $context->membership->id,
            ]);
            $workspaceApplication->applyAuditActor($context->user->id)->save();

            $workspaceApplication->fill([
                'status' => WorkspaceApplicationStatus::Active,
                'enabled_at' => now(),
            ]);
            $workspaceApplication->applyAuditActor($context->user->id)->save();

            $workspaceApplication = $workspaceApplication->fresh(['application', 'organizationApplication', 'enabledByMembership']);

            $this->moduleLifecycleManager->runEnableWorkspaceHooks(
                $context,
                $organizationApplication->application->key,
            );

            return $workspaceApplication;
        });

        $this->moduleLifecycleManager->completeEnableWorkspace($context, $organizationApplication->application->key);

        return $workspaceApplication;
    }

    public function reEnable(TenantContext $context, string $workspaceApplicationPublicId): WorkspaceApplication
    {
        $workspaceApplication = $this->findForWorkspace($context, $workspaceApplicationPublicId);

        if (! in_array($workspaceApplication->status, [WorkspaceApplicationStatus::Disabled, WorkspaceApplicationStatus::Archived], true)) {
            throw new InvalidWorkspaceApplicationTransitionException(
                'Only disabled or archived workspace applications can be re-enabled.',
            );
        }

        $this->assertOrganizationEntitlementActive($workspaceApplication->organizationApplication);

        return $this->reactivateExisting($workspaceApplication, $context);
    }

    public function disable(TenantContext $context, string $workspaceApplicationPublicId): WorkspaceApplication
    {
        $workspaceApplication = $this->findForWorkspace($context, $workspaceApplicationPublicId);

        $this->assertMutableApplication($workspaceApplication);

        if ($workspaceApplication->status !== WorkspaceApplicationStatus::Active) {
            throw new InvalidWorkspaceApplicationTransitionException('Only active workspace applications can be disabled.');
        }

        $applicationKey = $workspaceApplication->application->key;

        DB::transaction(function () use ($context, $workspaceApplication, $applicationKey) {
            $workspaceApplication->status = WorkspaceApplicationStatus::Disabled;
            $workspaceApplication->applyAuditActor($context->user->id)->save();

            $this->moduleLifecycleManager->runDisableWorkspaceHooks($context, $applicationKey);
        });

        $this->moduleLifecycleManager->completeDisableWorkspace($context, $applicationKey);

        return $workspaceApplication->fresh(['application', 'organizationApplication', 'enabledByMembership']);
    }

    public function archive(TenantContext $context, string $workspaceApplicationPublicId): WorkspaceApplication
    {
        $workspaceApplication = $this->findForWorkspace($context, $workspaceApplicationPublicId);

        $this->assertMutableApplication($workspaceApplication);

        if (! in_array($workspaceApplication->status, [WorkspaceApplicationStatus::Active, WorkspaceApplicationStatus::Disabled], true)) {
            throw new InvalidWorkspaceApplicationTransitionException(
                'Only active or disabled workspace applications can be archived.',
            );
        }

        $workspaceApplication->status = WorkspaceApplicationStatus::Archived;
        $workspaceApplication->applyAuditActor($context->user->id)->save();

        $this->runtimeCacheInvalidator->invalidateTenantContext($context);

        return $workspaceApplication->fresh(['application', 'organizationApplication', 'enabledByMembership']);
    }

    public function remove(TenantContext $context, string $workspaceApplicationPublicId): void
    {
        $workspaceApplication = $this->findForWorkspace($context, $workspaceApplicationPublicId);

        $this->assertMutableApplication($workspaceApplication);

        if ($workspaceApplication->status === WorkspaceApplicationStatus::Removed) {
            throw new InvalidWorkspaceApplicationTransitionException('Workspace application is already removed.');
        }

        $workspaceApplication->status = WorkspaceApplicationStatus::Removed;
        $workspaceApplication->applyAuditActor($context->user->id)->save();
        $workspaceApplication->applyDeleteActor($context->user->id)->save();
        $workspaceApplication->delete();

        $this->runtimeCacheInvalidator->invalidateTenantContext($context);
    }

    public function resolveWorkspaceApplication(
        TenantContext $context,
        string $workspaceApplicationPublicId,
    ): WorkspaceApplication {
        return $this->findForWorkspace($context, $workspaceApplicationPublicId);
    }

    public function cascadeOrganizationUninstall(OrganizationApplication $organizationApplication, int $actorUserId): void
    {
        WorkspaceApplication::query()
            ->with('application')
            ->where('organization_application_id', $organizationApplication->id)
            ->where('status', '!=', WorkspaceApplicationStatus::Removed)
            ->whereNull('deleted_at')
            ->each(function (WorkspaceApplication $workspaceApplication) use ($actorUserId) {
                $workspaceApplication->status = WorkspaceApplicationStatus::Removed;
                $workspaceApplication->applyAuditActor($actorUserId)->save();
                $workspaceApplication->applyDeleteActor($actorUserId)->save();
                $workspaceApplication->delete();
            });
    }

    private function reactivateExisting(
        WorkspaceApplication $workspaceApplication,
        TenantContext $context,
    ): WorkspaceApplication {
        $applicationKey = $workspaceApplication->application->key;

        DB::transaction(function () use ($context, $workspaceApplication, $applicationKey) {
            $workspaceApplication->status = WorkspaceApplicationStatus::Active;
            $workspaceApplication->applyAuditActor($context->user->id)->save();

            $this->moduleLifecycleManager->runEnableWorkspaceHooks($context, $applicationKey);
        });

        $this->moduleLifecycleManager->completeEnableWorkspace($context, $applicationKey);

        return $workspaceApplication->fresh(['application', 'organizationApplication', 'enabledByMembership']);
    }

    private function findForWorkspace(
        TenantContext $context,
        string $workspaceApplicationPublicId,
    ): WorkspaceApplication {
        $workspaceApplication = WorkspaceApplication::query()
            ->with(['application', 'organizationApplication'])
            ->where('public_id', $workspaceApplicationPublicId)
            ->where('workspace_id', $context->workspace->id)
            ->where('organization_id', $context->organization->id)
            ->where('status', '!=', WorkspaceApplicationStatus::Removed)
            ->whereNull('deleted_at')
            ->first();

        if ($workspaceApplication === null) {
            throw new WorkspaceApplicationNotFoundException;
        }

        return $workspaceApplication;
    }

    private function assertOrganizationEntitlementActive(OrganizationApplication $organizationApplication): void
    {
        if ($organizationApplication->status !== OrganizationApplicationStatus::Active) {
            throw new OrganizationEntitlementRequiredException;
        }

        if ($organizationApplication->application->status !== ApplicationStatus::Active) {
            throw new OrganizationEntitlementRequiredException;
        }
    }

    private function assertMutableApplication(WorkspaceApplication $workspaceApplication): void
    {
        if ($workspaceApplication->application->is_core || $workspaceApplication->is_bootstrap) {
            throw new CoreWorkspaceApplicationProtectedException;
        }
    }
}
