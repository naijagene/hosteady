<?php

namespace App\Services\Form;

use App\Modules\Sdk\Form\Data\FormHealthReport;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class DynamicFormHealthService
{
    /**
     * @var list<string>
     */
    private const REQUIRED_TABLES = [
        'form_definitions',
        'form_submissions',
        'form_drafts',
        'form_activity_logs',
    ];

    public function __construct(
        private readonly EnterpriseTableHealthGuard $tableGuard,
        private readonly DynamicFormStatisticsService $statisticsService,
    ) {
    }

    public function health(?TenantContext $context = null): FormHealthReport
    {
        return $this->healthReport($context);
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.forms.enabled', true);

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
            'enabled' => (bool) config('heos.enterprise.forms.enabled', true),
            'definitions' => $stats->definitions,
            'submissions' => $stats->submissions,
            'drafts' => $stats->drafts,
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
            'submissions' => $report->submissions,
            'drafts' => $report->drafts,
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
            'submissions' => 0,
            'drafts' => 0,
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

    private function healthReport(?TenantContext $context): FormHealthReport
    {
        $enabled = (bool) config('heos.enterprise.forms.enabled', true);
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
            $warnings[] = 'Dynamic forms are disabled in configuration.';
            $status = 'warning';
        }

        if ($enabled && $missingTables === [] && $stats->definitions === 0) {
            $warnings[] = 'No form definitions are registered yet.';
            $status = 'warning';
        }

        if ($missingTables !== []) {
            $status = 'warning';
        }

        return new FormHealthReport(
            enabled: $enabled,
            definitions: $stats->definitions,
            submissions: $stats->submissions,
            drafts: $stats->drafts,
            warnings: $warnings,
            status: $status,
            missingTables: $missingTables,
        );
    }
}
