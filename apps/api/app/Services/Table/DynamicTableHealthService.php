<?php

namespace App\Services\Table;

use App\Modules\Sdk\Table\Data\TableHealthReport;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class DynamicTableHealthService
{
    /**
     * @var list<string>
     */
    private const REQUIRED_TABLES = [
        'table_definitions',
        'table_views',
        'table_activity_logs',
    ];

    public function __construct(
        private readonly EnterpriseTableHealthGuard $tableGuard,
        private readonly DynamicTableStatisticsService $statisticsService,
    ) {
    }

    public function health(?TenantContext $context = null): TableHealthReport
    {
        return $this->healthReport($context);
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.tables.enabled', true);

        return $this->tableGuard->assessWhenTablesPresent(
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
            'enabled' => (bool) config('heos.enterprise.tables.enabled', true),
            'definitions' => $stats->definitions,
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
        $missingTables = $this->tableGuard->missingTables(self::REQUIRED_TABLES);

        $assessment = [
            'enabled' => $report->enabled,
            'definitions' => $report->definitions,
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
        $missingTables = $this->tableGuard->missingTables(self::REQUIRED_TABLES);

        $assessment = [
            'enabled' => $enabled,
            'definitions' => 0,
            'views' => 0,
            'warnings' => array_map(
                fn (string $table): string => $this->tableGuard->missingTableWarning($table),
                $missingTables,
            ),
            'status' => 'warning',
        ];

        if ($missingTables !== []) {
            $assessment['missing_tables'] = $missingTables;
        }

        return $assessment;
    }

    private function healthReport(?TenantContext $context): TableHealthReport
    {
        $enabled = (bool) config('heos.enterprise.tables.enabled', true);
        $stats = $this->statisticsService->statisticsForScope(
            $context?->organization,
            $context?->workspace,
        );
        $missingTables = $this->tableGuard->missingTables(self::REQUIRED_TABLES);
        $warnings = array_map(
            fn (string $table): string => $this->tableGuard->missingTableWarning($table),
            $missingTables,
        );
        $status = 'healthy';

        if (! $enabled) {
            $warnings[] = 'Dynamic tables are disabled in configuration.';
            $status = 'warning';
        }

        if ($enabled && $missingTables === [] && $stats->definitions === 0) {
            $warnings[] = 'No table definitions are registered yet.';
            $status = 'warning';
        }

        if ($missingTables !== []) {
            $status = 'warning';
        }

        return new TableHealthReport(
            enabled: $enabled,
            definitions: $stats->definitions,
            views: $stats->views,
            warnings: $warnings,
            status: $status,
            missingTables: $missingTables,
        );
    }
}
