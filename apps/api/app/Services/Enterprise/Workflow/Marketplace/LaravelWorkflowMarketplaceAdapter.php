<?php

namespace App\Services\Enterprise\Workflow\Marketplace;

use App\Models\Organization;
use App\Models\WorkflowPackageInstall;
use App\Models\WorkflowPackageVersion as WorkflowPackageVersionModel;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Marketplace\Contracts\WorkflowMarketplacePort;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowCompatibilityReport;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowInstallRequest;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowInstallResult;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackage;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageReference;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageStatistics;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageVersion;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowRollbackResult;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowUpgradeResult;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowInstallStatus;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowPackageStatus;

class LaravelWorkflowMarketplaceAdapter implements WorkflowMarketplacePort
{
    public function __construct(
        private readonly WorkflowPackageService $packageService,
        private readonly WorkflowPackageExporterService $exporterService,
        private readonly WorkflowPackageInstallerService $installerService,
        private readonly WorkflowCompatibilityService $compatibilityService,
        private readonly WorkflowPackageStatisticsService $statisticsService,
        private readonly WorkflowMarketplaceAuditRecorder $auditRecorder,
    ) {
    }

    public function listPackages(EnterpriseScope $scope): array
    {
        return $this->packageService->list($scope);
    }

    public function showPackage(EnterpriseScope $scope, string $packagePublicId): WorkflowPackage
    {
        return $this->packageService->show($scope, $packagePublicId);
    }

    public function createPackage(
        EnterpriseScope $scope,
        array $payload,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowPackage {
        return $this->packageService->create($scope, $payload, $userId, $membershipId);
    }

    public function publishVersion(
        EnterpriseScope $scope,
        string $packagePublicId,
        array $payload,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowPackageVersion {
        return $this->packageService->publishVersion($scope, $packagePublicId, $payload, $userId, $membershipId);
    }

    public function exportPackage(EnterpriseScope $scope, string $packagePublicId): array
    {
        return $this->exporterService->export($scope, $packagePublicId);
    }

    public function importPackage(
        EnterpriseScope $scope,
        array $payload,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowPackage {
        return $this->exporterService->import($scope, $payload, $userId, $membershipId);
    }

    public function installPackage(
        EnterpriseScope $scope,
        WorkflowInstallRequest $request,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowInstallResult {
        return $this->installerService->install($scope, $request, $userId, $membershipId);
    }

    public function upgradeInstall(
        EnterpriseScope $scope,
        string $installPublicId,
        array $payload,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowUpgradeResult {
        return $this->installerService->upgrade($scope, $installPublicId, $payload, $userId, $membershipId);
    }

    public function rollbackInstall(
        EnterpriseScope $scope,
        string $installPublicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowRollbackResult {
        return $this->installerService->rollback($scope, $installPublicId, $userId, $membershipId);
    }

    public function uninstallPackage(
        EnterpriseScope $scope,
        string $installPublicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowInstallResult {
        return $this->installerService->uninstall($scope, $installPublicId, $userId, $membershipId);
    }

    public function listInstalled(EnterpriseScope $scope): array
    {
        $organizationId = Organization::query()->where('public_id', $scope->organizationPublicId)->value('id');
        $workspaceId = null;

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $organizationId)
                ->value('id');
        }

        return WorkflowPackageInstall::query()
            ->with(['workflowPackage', 'workflowPackageVersion', 'installedWorkflowDefinition'])
            ->where('organization_id', $organizationId)
            ->when($workspaceId !== null, fn ($q) => $q->where('workspace_id', $workspaceId))
            ->whereIn('status', [WorkflowInstallStatus::Installed, WorkflowInstallStatus::RolledBack])
            ->orderByDesc('installed_at')
            ->get()
            ->map(fn (WorkflowPackageInstall $install) => new WorkflowInstallResult(
                installPublicId: $install->public_id,
                packagePublicId: $install->workflowPackage->public_id,
                packageVersionPublicId: $install->workflowPackageVersion->public_id,
                installedVersion: $install->installed_version,
                status: $install->status->value,
                installedWorkflowDefinitionPublicId: $install->installedWorkflowDefinition?->public_id,
                metadata: $install->metadata ?? [],
            ))
            ->all();
    }

    public function listUpdates(EnterpriseScope $scope): array
    {
        $organizationId = Organization::query()->where('public_id', $scope->organizationPublicId)->value('id');
        $workspaceId = null;

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $organizationId)
                ->value('id');
        }

        $installs = WorkflowPackageInstall::query()
            ->with(['workflowPackage.versions'])
            ->where('organization_id', $organizationId)
            ->when($workspaceId !== null, fn ($q) => $q->where('workspace_id', $workspaceId))
            ->whereIn('status', [WorkflowInstallStatus::Installed, WorkflowInstallStatus::RolledBack])
            ->get();

        $updates = [];

        foreach ($installs as $install) {
            $publishedVersions = WorkflowPackageVersionModel::query()
                ->where('workflow_package_id', $install->workflow_package_id)
                ->where('status', WorkflowPackageStatus::Published)
                ->get();

            $latest = $publishedVersions->reduce(
                fn (?WorkflowPackageVersionModel $carry, WorkflowPackageVersionModel $version) => $carry === null || version_compare($version->version, $carry->version, '>')
                    ? $version
                    : $carry,
            );

            if ($latest !== null && version_compare($latest->version, $install->installed_version, '>')) {
                $updates[] = new WorkflowPackageReference(
                    publicId: $install->workflowPackage->public_id,
                    packageKey: $install->workflowPackage->package_key,
                    name: $install->workflowPackage->name,
                    status: $install->workflowPackage->status->value,
                    visibility: $install->workflowPackage->visibility->value,
                    type: $install->workflowPackage->type->value,
                    moduleKey: $install->workflowPackage->module_key,
                    latestVersion: $latest->version,
                );
            }
        }

        return $updates;
    }

    public function checkCompatibility(EnterpriseScope $scope, string $packagePublicId): WorkflowCompatibilityReport
    {
        $report = $this->compatibilityService->check($scope, $packagePublicId);
        $package = \App\Models\WorkflowPackage::query()->where('public_id', $packagePublicId)->first();

        if ($package !== null) {
            $this->auditRecorder->recordCompatibilityChecked($package);
        }

        return $report;
    }

    public function statistics(EnterpriseScope $scope): WorkflowPackageStatistics
    {
        $organizationId = Organization::query()->where('public_id', $scope->organizationPublicId)->value('id');
        $workspaceId = null;

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $organizationId)
                ->value('id');
        }

        return $this->statisticsService->statistics($scope, $organizationId, $workspaceId);
    }
}
