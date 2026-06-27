<?php

namespace App\Services\Module\Development;

use App\Modules\Sdk\Development\Contracts\BusinessModuleHealthProvider;
use App\Modules\Sdk\Development\Data\BusinessModuleHealthReport;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class BusinessModuleHealthService implements BusinessModuleHealthProvider
{
    public function __construct(
        private readonly EnterpriseTableHealthGuard $tableGuard,
        private readonly BusinessModuleStatisticsService $statisticsService,
    ) {
    }

    public function health(): BusinessModuleHealthReport
    {
        return $this->healthReport(null);
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.business_modules.enabled', true);

        return $this->tableGuard->assessWhenTablesPresent(
            ['business_modules', 'business_module_installations'],
            fn (): array => $this->assessWithTables($context, $enabled),
            $this->fallbackAssessment($enabled),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function assessWithTables(?TenantContext $context, bool $enabled): array
    {
        $report = $this->healthReport($context);

        return [
            'enabled' => $report->enabled,
            'registered' => $report->registered,
            'installed' => $report->installed,
            'warnings' => $report->warnings,
            'status' => $report->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackAssessment(bool $enabled): array
    {
        return [
            'enabled' => $enabled,
            'registered' => 0,
            'installed' => 0,
            'warnings' => [],
            'status' => 'warning',
        ];
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
            'enabled' => (bool) config('heos.enterprise.business_modules.enabled', true),
            'registered' => $stats->registered,
            'installed' => $stats->installed,
            'enabled_count' => $stats->enabledCount,
            'disabled_count' => $stats->disabledCount,
        ];
    }

    private function healthReport(?TenantContext $context): BusinessModuleHealthReport
    {
        $enabled = (bool) config('heos.enterprise.business_modules.enabled', true);
        $stats = $this->statisticsService->statisticsForScope(
            $context?->organization,
            $context?->workspace,
        );
        $warnings = [];
        $status = 'healthy';

        if (! $enabled) {
            $warnings[] = 'Business modules are disabled in configuration.';
            $status = 'warning';
        }

        if ($enabled && $stats->registered === 0) {
            $warnings[] = 'No business modules are registered yet.';
            $status = 'warning';
        }

        return new BusinessModuleHealthReport(
            enabled: $enabled,
            registered: $stats->registered,
            installed: $stats->installed,
            warnings: $warnings,
            status: $status,
        );
    }
}
