<?php

namespace App\Services\DataRepository;

use App\Modules\Sdk\DataRepository\Data\EntityRecordHealthReport;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class EnterpriseEntityRecordHealthService
{
    /**
     * @var list<string>
     */
    private const REQUIRED_TABLES = [
        'enterprise_entity_records',
        'enterprise_entity_record_versions',
        'enterprise_entity_record_links',
        'enterprise_entity_record_activity',
    ];

    public function __construct(
        private readonly EnterpriseTableHealthGuard $tableGuard,
        private readonly EnterpriseEntityRecordStatisticsService $statisticsService,
    ) {
    }

    public function health(?TenantContext $context = null): EntityRecordHealthReport
    {
        return $this->healthReport($context);
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.data_repository.enabled', true);

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
            'enabled' => (bool) config('heos.enterprise.data_repository.enabled', true),
            'records' => $stats->records,
            'versions' => $stats->versions,
            'links' => $stats->links,
            'activity_logs' => $stats->activityLogs,
            'records_by_entity' => $stats->recordsByEntity,
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
            'records' => $report->records,
            'versions' => $report->versions,
            'links' => $report->links,
            'activity_logs' => $report->activityLogs,
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
            'records' => 0,
            'versions' => 0,
            'links' => 0,
            'activity_logs' => 0,
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

    private function healthReport(?TenantContext $context): EntityRecordHealthReport
    {
        $enabled = (bool) config('heos.enterprise.data_repository.enabled', true);
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
            $warnings[] = 'Enterprise data repository is disabled in configuration.';
            $status = 'warning';
        }

        if ($enabled && $missingTables === [] && $stats->records === 0) {
            $warnings[] = 'No entity records are stored yet.';
            $status = 'warning';
        }

        if ($missingTables !== []) {
            $status = 'warning';
        }

        return new EntityRecordHealthReport(
            enabled: $enabled,
            records: $stats->records,
            versions: $stats->versions,
            links: $stats->links,
            activityLogs: $stats->activityLogs,
            warnings: $warnings,
            status: $status,
            missingTables: $missingTables,
        );
    }
}
