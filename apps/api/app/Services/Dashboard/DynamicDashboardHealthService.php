<?php

namespace App\Services\Dashboard;

use App\Models\DashboardDefinition;
use App\Models\DashboardView;
use App\Models\DashboardWidget;
use App\Models\Organization;
use App\Models\Workspace;
use App\Modules\Sdk\Dashboard\Data\DashboardHealthReport;
use App\Modules\Sdk\Dashboard\Data\DashboardStatistics;
use App\Services\Enterprise\Support\EnterpriseDashboardHealthGuard;
use App\Support\Tenant\TenantContext;

class DynamicDashboardHealthService
{
    /**
     * @var list<string>
     */
    private const REQUIRED_TABLES = [
        'dashboard_definitions',
        'dashboard_widgets',
        'dashboard_views',
        'dashboard_activity_logs',
    ];

    public function __construct(
        private readonly EnterpriseDashboardHealthGuard $dashboardGuard,
        private readonly DynamicDashboardStatisticsService $statisticsService,
    ) {
    }

    public function health(?TenantContext $context = null): DashboardHealthReport
    {
        return $this->healthReport($context);
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.dashboards.enabled', true);

        return $this->dashboardGuard->assessWhenTablesPresent(
            self::REQUIRED_TABLES,
            fn (): array => $this->assessWithTables($context, $enabled),
            $this->fallbackAssessment($enabled),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function runtimeContribution(?TenantContext $context = null): array
    {
        $stats = $this->statisticsService->statisticsForScope(
            $context?->organization,
            $context?->workspace,
        );

        return [
            'enabled' => (bool) config('heos.enterprise.dashboards.enabled', true),
            'definitions' => $stats->definitions,
            'widgets' => $stats->widgets,
            'views' => $stats->views,
            'registered_modules' => $stats->registeredModules,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function assessWithTables(?TenantContext $context, bool $enabled): array
    {
        $report = $this->healthReport($context);
        $missingTables = $this->dashboardGuard->missingTables(self::REQUIRED_TABLES);

        $assessment = [
            'enabled' => $report->enabled,
            'definitions' => $report->definitions,
            'widgets' => $report->widgets,
            'views' => $report->views,
            'warnings' => $report->warnings,
            'status' => $report->status,
        ];

        if ($missingTables !== []) {
            $assessment['missing_tables'] = $missingTables;
        }

        return $assessment;
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackAssessment(bool $enabled): array
    {
        $missingTables = $this->dashboardGuard->missingTables(self::REQUIRED_TABLES);

        $assessment = [
            'enabled' => $enabled,
            'definitions' => 0,
            'widgets' => 0,
            'views' => 0,
            'warnings' => array_map(
                fn (string $table): string => $this->dashboardGuard->missingTableWarning($table),
                $missingTables,
            ),
            'status' => 'warning',
        ];

        if ($missingTables !== []) {
            $assessment['missing_tables'] = $missingTables;
        }

        return $assessment;
    }

    private function healthReport(?TenantContext $context): DashboardHealthReport
    {
        $enabled = (bool) config('heos.enterprise.dashboards.enabled', true);
        $stats = $this->statisticsService->statisticsForScope(
            $context?->organization,
            $context?->workspace,
        );
        $missingTables = $this->dashboardGuard->missingTables(self::REQUIRED_TABLES);
        $warnings = array_map(
            fn (string $table): string => $this->dashboardGuard->missingTableWarning($table),
            $missingTables,
        );
        $status = 'healthy';

        if (! $enabled) {
            $warnings[] = 'Dynamic dashboards are disabled in configuration.';
            $status = 'warning';
        }

        if ($enabled && $missingTables === [] && $stats->definitions === 0) {
            $warnings[] = 'No dashboard definitions are registered yet.';
            $status = 'warning';
        }

        if ($missingTables !== []) {
            $status = 'warning';
        }

        return new DashboardHealthReport(
            enabled: $enabled,
            definitions: $stats->definitions,
            widgets: $stats->widgets,
            views: $stats->views,
            warnings: $warnings,
            status: $status,
            missingTables: $missingTables,
        );
    }
}
