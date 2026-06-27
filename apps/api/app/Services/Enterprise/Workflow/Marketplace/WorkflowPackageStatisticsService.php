<?php

namespace App\Services\Enterprise\Workflow\Marketplace;

use App\Models\Organization;
use App\Models\WorkflowPackage;
use App\Models\WorkflowPackageInstall;
use App\Models\WorkflowPackageVersion;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageStatistics;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowInstallStatus;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowPackageStatus;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class WorkflowPackageStatisticsService
{
    public function statistics(
        EnterpriseScope $scope,
        ?string $organizationId = null,
        ?string $workspaceId = null,
    ): WorkflowPackageStatistics {
        $packageQuery = WorkflowPackage::query();

        if ($organizationId !== null) {
            $packageQuery->where(function ($query) use ($organizationId) {
                $query->whereNull('organization_id')->orWhere('organization_id', $organizationId);
            });
        }

        if ($workspaceId !== null) {
            $packageQuery->where(function ($query) use ($workspaceId) {
                $query->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        $packages = (clone $packageQuery)->count();
        $packageIds = (clone $packageQuery)->pluck('id');
        $versions = WorkflowPackageVersion::query()->whereIn('workflow_package_id', $packageIds)->count();

        $installQuery = WorkflowPackageInstall::query()
            ->whereIn('status', [WorkflowInstallStatus::Installed, WorkflowInstallStatus::RolledBack]);

        if ($organizationId !== null) {
            $installQuery->where('organization_id', $organizationId);
        }

        if ($workspaceId !== null) {
            $installQuery->where('workspace_id', $workspaceId);
        }

        $installs = (clone $installQuery)->count();
        $updatesAvailable = $this->countUpdatesAvailable($organizationId, $workspaceId);
        $featured = (clone $packageQuery)->where('status', 'published')->count();
        $compatible = $packages;

        return new WorkflowPackageStatistics(
            packages: $packages,
            versions: $versions,
            installs: $installs,
            updatesAvailable: $updatesAvailable,
            featured: $featured,
            compatible: $compatible,
        );
    }

    private function countUpdatesAvailable(?string $organizationId, ?string $workspaceId): int
    {
        if ($organizationId === null) {
            return 0;
        }

        $installs = WorkflowPackageInstall::query()
            ->with(['workflowPackage.versions', 'workflowPackageVersion'])
            ->where('organization_id', $organizationId)
            ->when($workspaceId !== null, fn ($q) => $q->where('workspace_id', $workspaceId))
            ->whereIn('status', ['installed', 'rolled_back'])
            ->get();

        $count = 0;

        foreach ($installs as $install) {
            $latest = $install->workflowPackage?->versions
                ->filter(fn ($version) => $version->status === WorkflowPackageStatus::Published)
                ->sortByDesc('published_at')
                ->first();

            if ($latest !== null && version_compare($latest->version, $install->installed_version, '>')) {
                $count++;
            }
        }

        return $count;
    }
}
