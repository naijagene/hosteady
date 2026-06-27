<?php

namespace App\Services\Table;

use App\Models\Organization;
use App\Models\TableDefinition;
use App\Models\TableView;
use App\Models\Workspace;
use App\Modules\Sdk\Table\Data\TableStatistics;

class DynamicTableStatisticsService
{
    public function statistics(
        ?string $organizationId = null,
        ?string $workspaceId = null,
    ): TableStatistics {
        $definitionQuery = TableDefinition::query();
        $viewQuery = TableView::query();

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

        $registeredModules = TableDefinition::query()
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

        return new TableStatistics(
            definitions: $definitionQuery->count(),
            views: $viewQuery->count(),
            registeredModules: $registeredModules,
        );
    }

    public function statisticsForScope(?Organization $organization = null, ?Workspace $workspace = null): TableStatistics
    {
        return $this->statistics($organization?->id, $workspace?->id);
    }
}
