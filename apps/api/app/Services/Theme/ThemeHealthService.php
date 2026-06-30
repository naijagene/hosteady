<?php

namespace App\Services\Theme;

use App\Modules\Sdk\Theme\Data\ThemeHealthReport;
use App\Support\Tenant\TenantContext;

class ThemeHealthService
{
    public function __construct(
        private readonly ThemeStatisticsService $statisticsService,
        private readonly ThemeTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function health(?TenantContext $context = null): ThemeHealthReport
    {
        $enabled = (bool) config('heos.enterprise.themes.enabled', true);
        $missingTables = $this->tableHealthSupport->missingCoreTables();
        $warnings = $this->tableHealthSupport->warningsForCoreTables();

        if (! $enabled) {
            $warnings[] = 'Theme framework is disabled.';
        }

        $stats = $context !== null
            ? $this->statisticsService->statisticsForScope($context->organization, $context->workspace)
            : $this->statisticsService->statisticsPlatformWide();

        if ($stats->definitions === 0 && $missingTables === []) {
            $warnings[] = 'No theme definitions registered.';
        }

        return new ThemeHealthReport(
            enabled: $enabled,
            healthy: $enabled && $missingTables === [],
            status: $missingTables === [] ? 'ok' : 'warning',
            definitions: $stats->definitions,
            versions: $stats->versions,
            brandProfiles: $stats->brandProfiles,
            warnings: $warnings,
            missingTables: $missingTables,
            statistics: $stats->toArray(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(): array
    {
        $health = $this->health();

        return $health->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function runtimeContribution(TenantContext $context): array
    {
        return $this->health($context)->toArray();
    }
}
