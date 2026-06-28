<?php

namespace App\Services\Ui;

use App\Modules\Sdk\Ui\Data\UiHealthReport;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class UiHealthService
{
    public function __construct(
        private readonly EnterpriseTableHealthGuard $tableGuard,
        private readonly UiTableHealthSupport $tableHealthSupport,
        private readonly UiStatisticsService $statisticsService,
    ) {
    }

    public function health(?TenantContext $context = null): UiHealthReport
    {
        $enabled = (bool) config('heos.enterprise.ui_metadata.enabled', true);
        $missingTables = $this->tableHealthSupport->missingCoreTables();
        $warnings = $this->tableHealthSupport->warningsForCoreTables();
        $stats = $missingTables === []
            ? $this->statisticsService->statisticsForScope($context?->organization, $context?->workspace)
            : $this->tableHealthSupport->emptyStatistics();

        if ($missingTables === [] && $enabled && $context !== null && $stats->pages === 0) {
            $warnings[] = 'No UI pages registered.';
        }

        $status = match (true) {
            $missingTables !== [] => 'warning',
            $warnings !== [] => 'degraded',
            default => 'healthy',
        };

        return new UiHealthReport(
            enabled: $enabled,
            healthy: $missingTables === [] && $warnings === [],
            status: $status,
            pages: $stats->pages,
            layouts: $stats->layouts,
            components: $stats->components,
            personalizations: $stats->personalizations,
            warnings: $warnings,
            missingTables: $missingTables,
            statistics: $stats->toArray(),
        );
    }

    /** @return array<string, mixed> */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.ui_metadata.enabled', true);

        return $this->tableGuard->assessWhenTablesPresent(
            UiTableHealthSupport::CORE_TABLES,
            fn (): array => $this->assessWithTables($context, $enabled),
            $this->tableHealthSupport->fallbackHealthAssessment($enabled),
        );
    }

    /** @return array<string, mixed> */
    public function runtimeContribution(?TenantContext $context = null): array
    {
        if (! $this->tableHealthSupport->coreTablesPresent()) {
            return [
                'enabled' => (bool) config('heos.enterprise.ui_metadata.enabled', true),
                'pages' => 0,
                'layouts' => 0,
                'components' => 0,
                'personalizations' => 0,
                'registered_modules' => 0,
                'status' => 'warning',
                'missing_tables' => $this->tableHealthSupport->missingCoreTables(),
                'warnings' => $this->tableHealthSupport->warningsForCoreTables(),
            ];
        }

        $stats = $this->statisticsService->statisticsForScope($context?->organization, $context?->workspace);

        return [
            'enabled' => (bool) config('heos.enterprise.ui_metadata.enabled', true),
            'pages' => $stats->pages,
            'layouts' => $stats->layouts,
            'components' => $stats->components,
            'personalizations' => $stats->personalizations,
            'registered_modules' => $stats->registeredModules,
        ];
    }

    /** @return array<string, mixed> */
    private function assessWithTables(?TenantContext $context, bool $enabled): array
    {
        $report = $this->health($context);

        return [
            'enabled' => $report->enabled,
            'healthy' => $report->healthy,
            'status' => $report->status,
            'pages' => $report->pages,
            'layouts' => $report->layouts,
            'components' => $report->components,
            'personalizations' => $report->personalizations,
            'warnings' => $report->warnings,
            'missing_tables' => $report->missingTables,
            'statistics' => $report->statistics,
        ];
    }
}
