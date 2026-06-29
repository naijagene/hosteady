<?php

namespace App\Services\Navigation;

use App\Modules\Sdk\Navigation\Data\NavigationHealthReport;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class NavigationHealthService
{
    public function __construct(
        private readonly EnterpriseTableHealthGuard $tableGuard,
        private readonly NavigationTableHealthSupport $tableHealthSupport,
        private readonly NavigationStatisticsService $statisticsService,
    ) {
    }

    public function health(?TenantContext $context = null): NavigationHealthReport
    {
        $enabled = (bool) config('heos.enterprise.navigation_designer.enabled', true);
        $missingTables = $this->tableHealthSupport->missingCoreTables();
        $warnings = $this->tableHealthSupport->warningsForCoreTables();
        $stats = $missingTables === []
            ? $this->statisticsService->statisticsForScope($context?->organization, $context?->workspace)
            : $this->tableHealthSupport->emptyStatistics();

        if ($missingTables === [] && $enabled && $context !== null && $stats->definitions === 0) {
            $warnings[] = 'No navigation definitions registered.';
        }

        $status = match (true) {
            $missingTables !== [] => 'warning',
            $warnings !== [] => 'degraded',
            default => 'healthy',
        };

        return new NavigationHealthReport(
            enabled: $enabled,
            healthy: $missingTables === [] && $warnings === [],
            status: $status,
            definitions: $stats->definitions,
            versions: $stats->versions,
            items: $stats->items,
            personalizations: $stats->personalizations,
            warnings: $warnings,
            missingTables: $missingTables,
            statistics: $stats->toArray(),
        );
    }

    /** @return array<string, mixed> */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.navigation_designer.enabled', true);

        return $this->tableGuard->assessWhenTablesPresent(
            NavigationTableHealthSupport::CORE_TABLES,
            fn (): array => $this->assessWithTables($context, $enabled),
            $this->tableHealthSupport->fallbackHealthAssessment($enabled),
        );
    }

    /** @return array<string, mixed> */
    public function runtimeContribution(?TenantContext $context = null): array
    {
        if (! $this->tableHealthSupport->coreTablesPresent()) {
            return [
                'enabled' => (bool) config('heos.enterprise.navigation_designer.enabled', true),
                'definitions' => 0,
                'versions' => 0,
                'items' => 0,
                'personalizations' => 0,
                'registered_modules' => 0,
                'status' => 'warning',
                'missing_tables' => $this->tableHealthSupport->missingCoreTables(),
                'warnings' => $this->tableHealthSupport->warningsForCoreTables(),
            ];
        }

        $stats = $this->statisticsService->statisticsForScope($context?->organization, $context?->workspace);

        return [
            'enabled' => (bool) config('heos.enterprise.navigation_designer.enabled', true),
            'definitions' => $stats->definitions,
            'versions' => $stats->versions,
            'items' => $stats->items,
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
            'definitions' => $report->definitions,
            'versions' => $report->versions,
            'items' => $report->items,
            'personalizations' => $report->personalizations,
            'warnings' => $report->warnings,
            'missing_tables' => $report->missingTables,
            'statistics' => $report->statistics,
        ];
    }
}
