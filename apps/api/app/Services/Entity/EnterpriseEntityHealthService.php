<?php

namespace App\Services\Entity;

use App\Modules\Sdk\Entity\Data\EntityHealthReport;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class EnterpriseEntityHealthService
{
    /**
     * @var list<string>
     */
    private const REQUIRED_TABLES = [
        'entity_definitions',
        'entity_relationships',
        'entity_activity_logs',
        'entity_comments',
        'entity_tags',
        'entity_taggables',
    ];

    public function __construct(
        private readonly EnterpriseTableHealthGuard $tableGuard,
        private readonly EnterpriseEntityStatisticsService $statisticsService,
    ) {
    }

    public function health(?TenantContext $context = null): EntityHealthReport
    {
        return $this->healthReport($context);
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.entities.enabled', true);

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
            'enabled' => (bool) config('heos.enterprise.entities.enabled', true),
            'definitions' => $stats->definitions,
            'relationships' => $stats->relationships,
            'comments' => $stats->comments,
            'tags' => $stats->tags,
            'activity_logs' => $stats->activityLogs,
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
            'relationships' => $report->relationships,
            'comments' => $report->comments,
            'tags' => $report->tags,
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
            'relationships' => 0,
            'comments' => 0,
            'tags' => 0,
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

    private function healthReport(?TenantContext $context): EntityHealthReport
    {
        $enabled = (bool) config('heos.enterprise.entities.enabled', true);
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
            $warnings[] = 'Enterprise entities are disabled in configuration.';
            $status = 'warning';
        }

        if ($enabled && $missingTables === [] && $stats->definitions === 0) {
            $warnings[] = 'No entity definitions are registered yet.';
            $status = 'warning';
        }

        if ($missingTables !== []) {
            $status = 'warning';
        }

        return new EntityHealthReport(
            enabled: $enabled,
            definitions: $stats->definitions,
            relationships: $stats->relationships,
            comments: $stats->comments,
            tags: $stats->tags,
            warnings: $warnings,
            status: $status,
            missingTables: $missingTables,
        );
    }
}
