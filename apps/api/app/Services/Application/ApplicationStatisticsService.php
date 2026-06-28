<?php

namespace App\Services\Application;

use App\Models\ApplicationRuntime\ApplicationNavigation;
use App\Models\ApplicationRuntime\ApplicationRuntimeApp;
use App\Models\ApplicationRuntime\ApplicationWorkspace as ApplicationWorkspaceModel;
use App\Models\Organization;
use App\Models\Workspace;
use App\Modules\Sdk\Application\Data\ApplicationStatistics;
use App\Modules\Sdk\Application\Enums\ApplicationStatus;

class ApplicationStatisticsService
{
    public function statisticsForScope(?Organization $organization, ?Workspace $workspace): ApplicationStatistics
    {
        if ($organization === null) {
            return new ApplicationStatistics(0, 0, 0, 0);
        }

        $appsQuery = ApplicationRuntimeApp::query();
        ApplicationRuntimeMapper::applyOrganizationScope($appsQuery, $organization->id);
        ApplicationRuntimeMapper::applyWorkspaceScope($appsQuery, $workspace?->id);

        $registered = (clone $appsQuery)->count();
        $enabled = (clone $appsQuery)->where('status', ApplicationStatus::Enabled->value)->count();

        $navQuery = ApplicationNavigation::query();
        ApplicationRuntimeMapper::applyOrganizationScope($navQuery, $organization->id);
        ApplicationRuntimeMapper::applyWorkspaceScope($navQuery, $workspace?->id);
        $navigationNodes = $navQuery->count();

        $workspaceQuery = ApplicationWorkspaceModel::query();
        ApplicationRuntimeMapper::applyOrganizationScope($workspaceQuery, $organization->id);
        ApplicationRuntimeMapper::applyWorkspaceScope($workspaceQuery, $workspace?->id);
        $workspaceCount = $workspaceQuery->count();

        return new ApplicationStatistics(
            registeredApps: $registered,
            enabledApps: $enabled,
            navigationNodes: $navigationNodes,
            workspaceCount: $workspaceCount,
        );
    }
}
