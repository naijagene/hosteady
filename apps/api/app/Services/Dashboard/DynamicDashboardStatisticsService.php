<?php

namespace App\Services\Dashboard;

use App\Models\DashboardDefinition;
use App\Models\DashboardView;
use App\Models\DashboardWidget;
use App\Models\Organization;
use App\Models\Workspace;
use App\Modules\Sdk\Dashboard\Data\DashboardStatistics;

class DynamicDashboardStatisticsService
{
    public function statistics(
        ?string $organizationId = null,
        ?string $workspaceId = null,
    ): DashboardStatistics {
        $definitionQuery = DashboardDefinition::query();
        $widgetQuery = DashboardWidget::query();
        $viewQuery = DashboardView::query();

        if ($organizationId !== null) {
            $definitionQuery->where(function ($query) use ($organizationId, $workspaceId) {
                $query->where(function ($scoped) use ($organizationId, $workspaceId) {
                    $scoped->where('organization_id', $organizationId);
                    if ($workspaceId !== null) {
                        $scoped->where('workspace_id', $workspaceId);
                    } else {
                        $scoped->whereNull('workspace_id');
                    }
                })->orWhere(function ($global) {
                    $global->whereNull('organization_id')->whereNull('workspace_id');
                });
            });
            $viewQuery->where('organization_id', $organizationId);
        }

        if ($workspaceId !== null) {
            $viewQuery->where('workspace_id', $workspaceId);
        } elseif ($organizationId !== null) {
            $viewQuery->whereNull('workspace_id');
        }

        $registeredModules = DashboardDefinition::query()
            ->when($organizationId !== null, function ($query) use ($organizationId, $workspaceId) {
                $query->where(function ($scoped) use ($organizationId, $workspaceId) {
                    $scoped->where('organization_id', $organizationId);
                    if ($workspaceId !== null) {
                        $scoped->where('workspace_id', $workspaceId);
                    } else {
                        $scoped->whereNull('workspace_id');
                    }
                })->orWhere(function ($global) {
                    $global->whereNull('organization_id')->whereNull('workspace_id');
                });
            })
            ->distinct()
            ->orderBy('module_key')
            ->pluck('module_key')
            ->values()
            ->all();

        return new DashboardStatistics(
            definitions: $definitionQuery->count(),
            widgets: $widgetQuery->count(),
            views: $viewQuery->count(),
            registeredModules: $registeredModules,
        );
    }

    public function statisticsForScope(?Organization $organization = null, ?Workspace $workspace = null): DashboardStatistics
    {
        return $this->statistics($organization?->id, $workspace?->id);
    }
}
