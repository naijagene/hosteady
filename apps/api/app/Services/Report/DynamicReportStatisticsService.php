<?php

namespace App\Services\Report;

use App\Models\Organization;
use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\ReportSchedule;
use App\Models\ReportTemplate;
use App\Models\Workspace;
use App\Modules\Sdk\Report\Data\ReportStatistics;

class DynamicReportStatisticsService
{
    public function statistics(
        ?string $organizationId = null,
        ?string $workspaceId = null,
    ): ReportStatistics {
        $definitionQuery = ReportDefinition::query();
        $templateQuery = ReportTemplate::query();
        $runQuery = ReportRun::query();
        $exportQuery = ReportExport::query();
        $scheduleQuery = ReportSchedule::query();

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

            $runQuery->where('organization_id', $organizationId);
            $exportQuery->where('organization_id', $organizationId);
            $scheduleQuery->where('organization_id', $organizationId);
        }

        if ($workspaceId !== null) {
            $runQuery->where('workspace_id', $workspaceId);
            $exportQuery->where('workspace_id', $workspaceId);
            $scheduleQuery->where('workspace_id', $workspaceId);
        }

        $registeredModules = ReportDefinition::query()
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

        return new ReportStatistics(
            definitions: $definitionQuery->count(),
            templates: $templateQuery->count(),
            runs: $runQuery->count(),
            exports: $exportQuery->count(),
            schedules: $scheduleQuery->count(),
            registeredModules: $registeredModules,
        );
    }

    public function statisticsForScope(?Organization $organization = null, ?Workspace $workspace = null): ReportStatistics
    {
        return $this->statistics($organization?->id, $workspace?->id);
    }
}
