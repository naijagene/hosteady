<?php

namespace App\Services\Module\Development;

use App\Models\BusinessModule;
use App\Models\BusinessModuleInstallation;
use App\Models\Organization;
use App\Models\Workspace;
use App\Modules\Sdk\Development\Data\BusinessModuleStatistics;
use App\Modules\Sdk\Development\Enums\BusinessModuleInstallStatus;

class BusinessModuleStatisticsService
{
    public function statistics(
        ?string $organizationId = null,
        ?string $workspaceId = null,
    ): BusinessModuleStatistics {
        $registered = BusinessModule::query()->count();

        $installQuery = BusinessModuleInstallation::query()
            ->whereIn('status', [
                BusinessModuleInstallStatus::Installed,
                BusinessModuleInstallStatus::Enabled,
                BusinessModuleInstallStatus::Disabled,
            ]);

        if ($organizationId !== null) {
            $installQuery->where('organization_id', $organizationId);
        }

        if ($workspaceId !== null) {
            $installQuery->where('workspace_id', $workspaceId);
        }

        $installed = (clone $installQuery)->count();
        $enabledCount = (clone $installQuery)->where('status', BusinessModuleInstallStatus::Enabled)->count();
        $disabledCount = (clone $installQuery)->where('status', BusinessModuleInstallStatus::Disabled)->count();

        return new BusinessModuleStatistics(
            registered: $registered,
            installed: $installed,
            enabledCount: $enabledCount,
            disabledCount: $disabledCount,
        );
    }

    public function statisticsForScope(?Organization $organization = null, ?Workspace $workspace = null): BusinessModuleStatistics
    {
        return $this->statistics($organization?->id, $workspace?->id);
    }
}
