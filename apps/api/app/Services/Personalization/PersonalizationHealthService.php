<?php

namespace App\Services\Personalization;

use App\Modules\Sdk\Personalization\Data\PersonalizationHealthReport;
use App\Support\Tenant\TenantContext;

class PersonalizationHealthService
{
    public function __construct(
        private readonly PersonalizationStatisticsService $statisticsService,
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function health(?TenantContext $context = null): PersonalizationHealthReport
    {
        $enabled = (bool) config('heos.enterprise.personalization.enabled', true);
        $missingTables = $this->tableHealthSupport->missingCoreTables();
        $warnings = $this->tableHealthSupport->warningsForCoreTables();

        if (! $enabled) {
            $warnings[] = 'Personalization framework is disabled.';
        }

        if ($missingTables !== []) {
            return $this->tableHealthSupport->emptyHealthReport();
        }

        $stats = $context !== null
            ? $this->statisticsService->statisticsForScope($context->organization, $context->workspace)
            : [
                'profiles' => 0,
                'preferences' => 0,
                'favorites' => 0,
                'recent_items' => 0,
                'shortcuts' => 0,
                'onboarding_states' => 0,
            ];

        if (($stats['profiles'] ?? 0) === 0 && ($stats['preferences'] ?? 0) === 0) {
            $warnings[] = 'No personalization profiles or preferences registered.';
        }

        return new PersonalizationHealthReport(
            enabled: $enabled,
            healthy: $enabled && $missingTables === [],
            status: $missingTables === [] ? 'ok' : 'warning',
            profiles: (int) ($stats['profiles'] ?? 0),
            preferences: (int) ($stats['preferences'] ?? 0),
            favorites: (int) ($stats['favorites'] ?? 0),
            recentItems: (int) ($stats['recent_items'] ?? 0),
            shortcuts: (int) ($stats['shortcuts'] ?? 0),
            onboardingStates: (int) ($stats['onboarding_states'] ?? 0),
            warnings: $warnings,
            missingTables: $missingTables,
            statistics: $stats,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(): array
    {
        return $this->health()->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function runtimeContribution(TenantContext $context): array
    {
        return $this->health($context)->toArray();
    }
}
