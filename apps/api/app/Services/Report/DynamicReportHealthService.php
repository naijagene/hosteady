<?php

namespace App\Services\Report;

use App\Models\Organization;
use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\ReportSchedule;
use App\Models\ReportTemplate;
use App\Models\Workspace;
use App\Modules\Sdk\Report\Data\ReportHealthReport;
use App\Modules\Sdk\Report\Data\ReportStatistics;
use App\Services\Enterprise\Support\EnterpriseReportHealthGuard;
use App\Support\Tenant\TenantContext;

class DynamicReportHealthService
{
    /**
     * @var list<string>
     */
    private const REQUIRED_TABLES = [
        'report_definitions',
        'report_templates',
        'report_runs',
        'report_exports',
        'report_schedules',
        'report_activity_logs',
    ];

    public function __construct(
        private readonly EnterpriseReportHealthGuard $reportGuard,
        private readonly DynamicReportStatisticsService $statisticsService,
    ) {
    }

    public function health(?TenantContext $context = null): ReportHealthReport
    {
        return $this->healthReport($context);
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.reports.enabled', true);

        return $this->reportGuard->assessWhenTablesPresent(
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
            'enabled' => (bool) config('heos.enterprise.reports.enabled', true),
            'definitions' => $stats->definitions,
            'templates' => $stats->templates,
            'runs' => $stats->runs,
            'exports' => $stats->exports,
            'schedules' => $stats->schedules,
            'registered_modules' => $stats->registeredModules,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function assessWithTables(?TenantContext $context, bool $enabled): array
    {
        $report = $this->healthReport($context);
        $missingTables = $this->reportGuard->missingTables(self::REQUIRED_TABLES);

        $assessment = [
            'enabled' => $report->enabled,
            'definitions' => $report->definitions,
            'templates' => $report->templates,
            'runs' => $report->runs,
            'exports' => $report->exports,
            'schedules' => $report->schedules,
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
        $missingTables = $this->reportGuard->missingTables(self::REQUIRED_TABLES);

        $assessment = [
            'enabled' => $enabled,
            'definitions' => 0,
            'templates' => 0,
            'runs' => 0,
            'exports' => 0,
            'schedules' => 0,
            'warnings' => array_map(
                fn (string $table): string => $this->reportGuard->missingTableWarning($table),
                $missingTables,
            ),
            'status' => 'warning',
        ];

        if ($missingTables !== []) {
            $assessment['missing_tables'] = $missingTables;
        }

        return $assessment;
    }

    private function healthReport(?TenantContext $context): ReportHealthReport
    {
        $enabled = (bool) config('heos.enterprise.reports.enabled', true);
        $stats = $this->statisticsService->statisticsForScope(
            $context?->organization,
            $context?->workspace,
        );
        $missingTables = $this->reportGuard->missingTables(self::REQUIRED_TABLES);
        $warnings = array_map(
            fn (string $table): string => $this->reportGuard->missingTableWarning($table),
            $missingTables,
        );
        $status = 'healthy';

        if (! $enabled) {
            $warnings[] = 'Dynamic reports are disabled in configuration.';
            $status = 'warning';
        }

        if ($enabled && $missingTables === [] && $stats->definitions === 0) {
            $warnings[] = 'No report definitions are registered yet.';
            $status = 'warning';
        }

        if ($missingTables !== []) {
            $status = 'warning';
        }

        return new ReportHealthReport(
            enabled: $enabled,
            definitions: $stats->definitions,
            templates: $stats->templates,
            runs: $stats->runs,
            exports: $stats->exports,
            schedules: $stats->schedules,
            warnings: $warnings,
            status: $status,
            missingTables: $missingTables,
        );
    }
}
