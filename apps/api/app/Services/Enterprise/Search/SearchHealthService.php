<?php

namespace App\Services\Enterprise\Search;

use App\Models\PlatformSavedSearch;
use App\Models\PlatformSearchIndex;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class SearchHealthService
{
    public function __construct(
        private readonly SearchModuleRegistry $moduleRegistry,
        private readonly EnterpriseTableHealthGuard $tableGuard,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.search.enabled', true);

        return $this->tableGuard->assessWhenTablesPresent(
            ['platform_search_indexes', 'platform_saved_searches'],
            fn (): array => $this->assessWithTables($context, $enabled),
            $this->fallbackAssessment($enabled),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function assessWithTables(?TenantContext $context, bool $enabled): array
    {
        $warnings = [];

        if (! $enabled) {
            $warnings[] = 'Enterprise search is disabled in configuration.';
        }

        $indexQuery = PlatformSearchIndex::query()->whereNull('deleted_at');
        $savedQuery = PlatformSavedSearch::query()->whereNull('deleted_at');

        if ($context !== null) {
            $indexQuery->where('organization_id', $context->organization->id);
            $savedQuery->where('organization_id', $context->organization->id);
        }

        $indexCount = (clone $indexQuery)->count();
        $savedCount = (clone $savedQuery)->count();
        $supportedModules = $this->moduleRegistry->moduleKeys();

        if ($indexCount === 0) {
            $warnings[] = 'No search indexes are registered yet.';
        }

        return [
            'enabled' => $enabled,
            'index_count' => $indexCount,
            'indexed_entities' => $indexCount,
            'saved_searches' => $savedCount,
            'supported_modules' => $supportedModules,
            'warnings' => $warnings,
            'status' => $warnings === [] ? 'healthy' : 'warning',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackAssessment(bool $enabled): array
    {
        return [
            'enabled' => $enabled,
            'index_count' => 0,
            'indexed_entities' => 0,
            'saved_searches' => 0,
            'supported_modules' => $this->moduleRegistry->moduleKeys(),
            'warnings' => [],
            'status' => 'healthy',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function runtimeContribution(?TenantContext $context = null): array
    {
        $assessment = $this->assess($context);

        return [
            'enabled' => $assessment['enabled'],
            'indexed_entities' => $assessment['indexed_entities'],
            'saved_searches' => $assessment['saved_searches'],
            'supported_modules' => $assessment['supported_modules'],
        ];
    }
}
