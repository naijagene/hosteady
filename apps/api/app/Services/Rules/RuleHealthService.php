<?php

namespace App\Services\Rules;

use App\Modules\Sdk\Rules\Data\RuleHealthReport;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class RuleHealthService
{
    /** @var list<string> */
    private const REQUIRED_TABLES = [
        'rule_sets',
        'rule_definitions',
        'rule_evaluations',
        'rule_executions',
        'rule_activity_logs',
    ];

    public function __construct(
        private readonly EnterpriseTableHealthGuard $tableGuard,
        private readonly RuleStatisticsService $statisticsService,
    ) {
    }

    public function health(?TenantContext $context = null): RuleHealthReport
    {
        $enabled = (bool) config('heos.enterprise.business_rules.enabled', true);
        $missingTables = $this->tableGuard->missingTables(self::REQUIRED_TABLES);
        $stats = $this->statisticsService->statisticsForScope($context?->organization, $context?->workspace);

        return new RuleHealthReport(
            enabled: $enabled,
            healthy: $missingTables === [],
            missingTables: $missingTables,
            statistics: $stats->toArray(),
            warnings: $missingTables !== [] ? ['Missing business rules tables.'] : [],
        );
    }

    public function assess(?TenantContext $context = null): array
    {
        $report = $this->health($context);

        return [
            'enabled' => $report->enabled,
            'healthy' => $report->healthy,
            'missing_tables' => $report->missingTables,
            'statistics' => $report->statistics,
            'warnings' => $report->warnings,
        ];
    }

    public function runtimeContribution(?TenantContext $context = null): array
    {
        $stats = $this->statisticsService->statisticsForScope($context?->organization, $context?->workspace);

        return [
            'enabled' => (bool) config('heos.enterprise.business_rules.enabled', true),
            'rule_sets' => $stats->ruleSets,
            'rule_definitions' => $stats->ruleDefinitions,
            'evaluations' => $stats->evaluations,
            'executions' => $stats->executions,
        ];
    }
}
