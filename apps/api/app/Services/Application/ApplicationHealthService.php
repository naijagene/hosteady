<?php

namespace App\Services\Application;

use App\Models\ApplicationRuntime\ApplicationNavigation;
use App\Models\ApplicationRuntime\ApplicationRuntimeApp;
use App\Models\ApplicationRuntime\ApplicationWorkspace as ApplicationWorkspaceModel;
use App\Models\Organization;
use App\Models\Workspace;
use App\Modules\Sdk\Application\Data\ApplicationHealthReport;
use App\Modules\Sdk\Application\Data\ApplicationStatistics;
use App\Modules\Sdk\Application\Enums\ApplicationStatus;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class ApplicationHealthService
{
    /** @var list<string> */
    private const REQUIRED_TABLES = [
        'application_runtime_apps',
        'application_workspaces',
        'application_navigation',
        'application_runtime_cache',
    ];

    public function __construct(
        private readonly EnterpriseTableHealthGuard $tableGuard,
        private readonly ApplicationStatisticsService $statisticsService,
    ) {
    }

    public function health(?TenantContext $context = null): ApplicationHealthReport
    {
        $enabled = (bool) config('heos.enterprise.application_runtime.enabled', true);
        $missingTables = $this->tableGuard->missingTables(self::REQUIRED_TABLES);
        $stats = $this->statisticsService->statisticsForScope($context?->organization, $context?->workspace);
        $warnings = [];

        if ($missingTables !== []) {
            $warnings[] = 'Missing application runtime tables.';
        } elseif ($enabled && $context !== null && $stats->registeredApps === 0) {
            $warnings[] = 'No applications registered.';
        }

        return new ApplicationHealthReport(
            enabled: $enabled,
            healthy: $missingTables === [],
            status: $missingTables === [] ? ($warnings === [] ? 'healthy' : 'degraded') : 'critical',
            registeredApps: $stats->registeredApps,
            enabledApps: $stats->enabledApps,
            warnings: $warnings,
            missingTables: $missingTables,
            statistics: $stats->toArray(),
        );
    }

    public function assess(?TenantContext $context = null): array
    {
        $report = $this->health($context);

        return [
            'enabled' => $report->enabled,
            'healthy' => $report->healthy,
            'status' => $report->status,
            'registered_apps' => $report->registeredApps,
            'enabled_apps' => $report->enabledApps,
            'warnings' => $report->warnings,
            'missing_tables' => $report->missingTables,
            'statistics' => $report->statistics,
        ];
    }

    public function runtimeContribution(?TenantContext $context = null): array
    {
        $stats = $this->statisticsService->statisticsForScope($context?->organization, $context?->workspace);

        return [
            'enabled' => (bool) config('heos.enterprise.application_runtime.enabled', true),
            'registered_apps' => $stats->registeredApps,
            'enabled_apps' => $stats->enabledApps,
            'navigation_nodes' => $stats->navigationNodes,
            'workspace_count' => $stats->workspaceCount,
        ];
    }
}
