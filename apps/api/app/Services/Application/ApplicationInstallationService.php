<?php

namespace App\Services\Application;

use App\Enums\ApplicationStatus;
use App\Enums\OrganizationApplicationStatus;
use App\Exceptions\Application\ApplicationAlreadyInstalledException;
use App\Exceptions\Application\ApplicationNotAvailableException;
use App\Exceptions\Application\CoreApplicationProtectedException;
use App\Exceptions\Application\InvalidApplicationTransitionException;
use App\Exceptions\Application\OrganizationApplicationNotFoundException;
use App\Models\OrganizationApplication;
use App\Services\Runtime\RuntimeCacheInvalidator;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApplicationInstallationService
{
    public function __construct(
        private readonly ApplicationRegistryService $applicationRegistryService,
        private readonly \App\Services\Audit\DomainAuditRecorder $domainAuditRecorder,
        private readonly \App\Services\WorkspaceApplication\WorkspaceApplicationService $workspaceApplicationService,
        private readonly RuntimeCacheInvalidator $runtimeCacheInvalidator,
    ) {
    }

    /**
     * @return Collection<int, OrganizationApplication>
     */
    public function listInstalled(TenantContext $context): Collection
    {
        return OrganizationApplication::query()
            ->with(['application', 'installedByMembership'])
            ->where('organization_id', $context->organization->id)
            ->where('status', '!=', OrganizationApplicationStatus::Uninstalled)
            ->whereNull('deleted_at')
            ->orderBy('installed_at')
            ->get();
    }

    public function install(TenantContext $context, string $applicationPublicId): OrganizationApplication
    {
        $application = $this->applicationRegistryService->findByPublicId($applicationPublicId);

        if ($application->status !== ApplicationStatus::Active) {
            throw new ApplicationNotAvailableException;
        }

        $alreadyInstalled = OrganizationApplication::query()
            ->where('organization_id', $context->organization->id)
            ->where('application_id', $application->id)
            ->where('status', '!=', OrganizationApplicationStatus::Uninstalled)
            ->whereNull('deleted_at')
            ->exists();

        if ($alreadyInstalled) {
            throw new ApplicationAlreadyInstalledException;
        }

        return DB::transaction(function () use ($context, $application) {
            $installation = new OrganizationApplication([
                'organization_id' => $context->organization->id,
                'application_id' => $application->id,
                'status' => OrganizationApplicationStatus::Installing,
                'installed_version' => $application->version,
                'config' => [],
                'installed_at' => null,
                'installed_by_user_id' => $context->user->id,
                'installed_by_membership_id' => $context->membership->id,
            ]);
            $installation->applyAuditActor($context->user->id)->save();

            $installation->fill([
                'status' => OrganizationApplicationStatus::Active,
                'installed_at' => now(),
            ]);
            $installation->applyAuditActor($context->user->id)->save();

            $installation = $installation->fresh(['application', 'installedByMembership']);

            $this->domainAuditRecorder->recordApplicationInstalled($installation, $application, $context);

            return $installation;
        });
    }

    public function enable(TenantContext $context, string $installationPublicId): OrganizationApplication
    {
        $installation = $this->findInstallationForOrganization($context, $installationPublicId);

        if ($installation->status !== OrganizationApplicationStatus::Disabled) {
            throw new InvalidApplicationTransitionException('Only disabled applications can be enabled.');
        }

        $installation->status = OrganizationApplicationStatus::Active;
        $installation->applyAuditActor($context->user->id)->save();

        $installation = $installation->fresh(['application', 'installedByMembership']);

        $this->domainAuditRecorder->recordApplicationEnabled($installation, $context);

        return $installation;
    }

    public function disable(TenantContext $context, string $installationPublicId): OrganizationApplication
    {
        $installation = $this->findInstallationForOrganization($context, $installationPublicId);

        $this->assertNotCoreApplication($installation, $context, 'disable');

        if ($installation->status !== OrganizationApplicationStatus::Active) {
            throw new InvalidApplicationTransitionException('Only active applications can be disabled.');
        }

        $installation->status = OrganizationApplicationStatus::Disabled;
        $installation->applyAuditActor($context->user->id)->save();

        $installation = $installation->fresh(['application', 'installedByMembership']);

        $this->domainAuditRecorder->recordApplicationDisabled($installation, $context);

        $this->runtimeCacheInvalidator->invalidateForApplicationCatalogChange($installation->application_id);

        return $installation;
    }

    public function uninstall(TenantContext $context, string $installationPublicId): void
    {
        $installation = $this->findInstallationForOrganization($context, $installationPublicId);

        $this->assertNotCoreApplication($installation, $context, 'uninstall');

        if (! in_array($installation->status, [OrganizationApplicationStatus::Active, OrganizationApplicationStatus::Disabled], true)) {
            throw new InvalidApplicationTransitionException('Only active or disabled applications can be uninstalled.');
        }

        $this->domainAuditRecorder->recordApplicationUninstalled($installation, $context);

        $this->runtimeCacheInvalidator->invalidateForApplicationCatalogChange($installation->application_id);

        $this->workspaceApplicationService->cascadeOrganizationUninstall($installation, $context->user->id);

        $installation->status = OrganizationApplicationStatus::Uninstalled;
        $installation->applyAuditActor($context->user->id)->save();
        $installation->applyDeleteActor($context->user->id)->save();
        $installation->delete();
    }

    public function resolveInstallation(
        TenantContext $context,
        string $installationPublicId,
    ): OrganizationApplication {
        return $this->findInstallationForOrganization($context, $installationPublicId);
    }

    private function findInstallationForOrganization(
        TenantContext $context,
        string $installationPublicId,
    ): OrganizationApplication {
        $installation = OrganizationApplication::query()
            ->with('application')
            ->where('public_id', $installationPublicId)
            ->where('organization_id', $context->organization->id)
            ->where('status', '!=', OrganizationApplicationStatus::Uninstalled)
            ->whereNull('deleted_at')
            ->first();

        if ($installation === null) {
            throw new OrganizationApplicationNotFoundException;
        }

        return $installation;
    }

    private function assertNotCoreApplication(
        OrganizationApplication $installation,
        TenantContext $context,
        string $attemptedAction,
    ): void {
        if ($installation->application->is_core) {
            $this->domainAuditRecorder->recordCoreActionBlocked($installation, $context, $attemptedAction);

            throw new CoreApplicationProtectedException;
        }
    }
}
