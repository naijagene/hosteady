<?php

namespace App\Services\Navigation;

use App\Modules\Sdk\Navigation\Data\NavigationRenderPayload;
use App\Modules\Sdk\Navigation\Data\NavigationStatistics;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class NavigationTableHealthSupport
{
    /** @var list<string> */
    public const CORE_TABLES = [
        'navigation_definitions',
        'navigation_versions',
        'navigation_items',
        'navigation_personalizations',
    ];

    public function __construct(
        private readonly EnterpriseTableHealthGuard $tableGuard,
    ) {
    }

    /** @return list<string> */
    public function missingCoreTables(): array
    {
        return $this->tableGuard->missingTables(self::CORE_TABLES);
    }

    public function coreTablesPresent(): bool
    {
        return $this->missingCoreTables() === [];
    }

    public function isTablePresent(string $table): bool
    {
        return $this->tableGuard->missingTables([$table]) === [];
    }

    /** @return list<string> */
    public function warningsForTables(array $tables): array
    {
        return array_map(
            fn (string $table): string => $this->tableGuard->missingTableWarning($table),
            $this->tableGuard->missingTables($tables),
        );
    }

    /** @return list<string> */
    public function warningsForCoreTables(): array
    {
        return $this->warningsForTables(self::CORE_TABLES);
    }

    public function emptyStatistics(): NavigationStatistics
    {
        return new NavigationStatistics(0, 0, 0, 0, 0);
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>
     */
    public function missingTablesRuntimeContext(TenantContext $context, array $permissions = []): array
    {
        $missingTables = $this->missingCoreTables();

        return [
            'enabled' => (bool) config('heos.enterprise.navigation_designer.enabled', true),
            'status' => 'warning',
            'missing_tables' => $missingTables,
            'warnings' => $this->warningsForCoreTables(),
            'organization_public_id' => $context->organizationPublicId,
            'workspace_public_id' => $context->workspacePublicId,
            'definitions' => [],
            'items' => [],
            'statistics' => $this->emptyStatistics()->toArray(),
        ];
    }

    /**
     * @param  list<string>  $permissions
     */
    public function emptyRenderPayload(
        TenantContext $context,
        array $permissions,
        ?string $moduleKey = null,
        ?string $navigationKey = null,
    ): NavigationRenderPayload {
        $runtimeContext = $this->missingTablesRuntimeContext($context, $permissions);

        if ($moduleKey !== null) {
            $runtimeContext['module_key'] = $moduleKey;
        }

        if ($navigationKey !== null) {
            $runtimeContext['navigation_key'] = $navigationKey;
        }

        return new NavigationRenderPayload(
            definition: [],
            version: [],
            tree: [],
            items: [],
            permissions: $permissions,
            personalization: [],
            runtimeContext: $runtimeContext,
            warnings: $this->warningsForCoreTables(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function fallbackHealthAssessment(bool $enabled): array
    {
        $missingTables = $this->missingCoreTables();

        return [
            'enabled' => $enabled,
            'healthy' => false,
            'status' => 'warning',
            'definitions' => 0,
            'versions' => 0,
            'items' => 0,
            'personalizations' => 0,
            'warnings' => $this->warningsForCoreTables(),
            'missing_tables' => $missingTables,
            'statistics' => $this->emptyStatistics()->toArray(),
        ];
    }
}
