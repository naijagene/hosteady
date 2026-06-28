<?php

namespace App\Services\Integration;

use App\Modules\Sdk\Integration\Data\IntegrationHealthReport;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class IntegrationHealthService
{
    /** @var list<string> */
    private const REQUIRED_TABLES = [
        'integration_events',
        'integration_event_subscriptions',
        'integration_connectors',
        'integration_endpoints',
        'integration_credentials',
        'integration_mappings',
        'integration_dispatches',
        'integration_dead_letters',
        'integration_activity_logs',
    ];

    public function __construct(
        private readonly EnterpriseTableHealthGuard $tableGuard,
        private readonly IntegrationStatisticsService $statisticsService,
    ) {
    }

    public function health(?TenantContext $context = null): IntegrationHealthReport
    {
        $enabled = (bool) config('heos.enterprise.integrations.enabled', true);
        $missingTables = $this->tableGuard->missingTables(self::REQUIRED_TABLES);
        $stats = $this->statisticsService->statisticsForScope($context?->organization, $context?->workspace);

        return new IntegrationHealthReport(
            enabled: $enabled,
            healthy: $missingTables === [],
            status: $missingTables === [] ? 'healthy' : 'degraded',
            events: $stats->events,
            subscriptions: $stats->subscriptions,
            connectors: $stats->connectors,
            endpoints: $stats->endpoints,
            dispatches: $stats->dispatches,
            deadLetters: $stats->deadLetters,
            missingTables: $missingTables,
            warnings: $missingTables !== [] ? ['Missing integration tables.'] : [],
            statistics: $stats->toArray(),
        );
    }

    public function assess(?TenantContext $context = null): array
    {
        $report = $this->health($context);

        return [
            'enabled' => $report->enabled,
            'healthy' => $report->healthy,
            'status' => $report->status,
            'missing_tables' => $report->missingTables,
            'statistics' => $report->statistics,
            'warnings' => $report->warnings,
        ];
    }

    public function runtimeContribution(?TenantContext $context = null): array
    {
        $stats = $this->statisticsService->statisticsForScope($context?->organization, $context?->workspace);

        return [
            'enabled' => (bool) config('heos.enterprise.integrations.enabled', true),
            'events' => $stats->events,
            'subscriptions' => $stats->subscriptions,
            'connectors' => $stats->connectors,
            'endpoints' => $stats->endpoints,
            'dispatches' => $stats->dispatches,
            'dead_letters' => $stats->deadLetters,
        ];
    }
}
