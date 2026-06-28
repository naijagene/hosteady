<?php

namespace App\Services\Ui;

use App\Modules\Sdk\Ui\Data\UiRenderPayload;
use App\Modules\Sdk\Ui\Data\UiStatistics;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class UiTableHealthSupport
{
    /** @var list<string> */
    public const CORE_TABLES = [
        'ui_pages',
        'ui_layouts',
        'ui_components',
        'ui_personalizations',
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

    public function emptyStatistics(): UiStatistics
    {
        return new UiStatistics(0, 0, 0, 0, 0);
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>
     */
    public function missingTablesRuntimeContext(TenantContext $context, array $permissions = []): array
    {
        $missingTables = $this->missingCoreTables();

        return [
            'enabled' => (bool) config('heos.enterprise.ui_metadata.enabled', true),
            'status' => 'warning',
            'missing_tables' => $missingTables,
            'warnings' => $this->warningsForCoreTables(),
            'organization_public_id' => $context->organizationPublicId,
            'workspace_public_id' => $context->workspacePublicId,
            'pages' => [],
            'bindings' => [],
            'statistics' => $this->emptyStatistics()->toArray(),
        ];
    }

    /**
     * @param  list<string>  $permissions
     */
    public function emptyRenderPayload(TenantContext $context, array $permissions, ?string $moduleKey = null, ?string $pageKey = null): UiRenderPayload
    {
        $runtimeContext = $this->missingTablesRuntimeContext($context, $permissions);

        if ($moduleKey !== null) {
            $runtimeContext['module_key'] = $moduleKey;
        }

        if ($pageKey !== null) {
            $runtimeContext['page_key'] = $pageKey;
        }

        return new UiRenderPayload(
            page: [],
            layout: [],
            regions: [],
            components: [],
            actions: [],
            conditions: [],
            breakpoints: [],
            theme: [],
            personalization: [],
            permissions: $permissions,
            runtimeContext: $runtimeContext,
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
            'pages' => 0,
            'layouts' => 0,
            'components' => 0,
            'personalizations' => 0,
            'warnings' => $this->warningsForCoreTables(),
            'missing_tables' => $missingTables,
            'statistics' => $this->emptyStatistics()->toArray(),
        ];
    }
}
