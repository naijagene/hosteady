<?php

namespace App\Services\Theme;

use App\Modules\Sdk\Theme\Data\ThemeRenderPayload;
use App\Modules\Sdk\Theme\Data\ThemeStatistics;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class ThemeTableHealthSupport
{
    /** @var list<string> */
    public const CORE_TABLES = [
        'theme_definitions',
        'brand_profiles',
        'theme_versions',
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
    public function warningsForCoreTables(): array
    {
        return array_map(
            fn (string $table): string => $this->tableGuard->missingTableWarning($table),
            $this->missingCoreTables(),
        );
    }

    public function emptyStatistics(): ThemeStatistics
    {
        return new ThemeStatistics(0, 0, 0, 0, 0);
    }

    /**
     * @param  array<string, bool>  $permissions
     */
    public function emptyRenderPayload(
        TenantContext $context,
        array $permissions,
        ?string $moduleKey = null,
        ?string $themeKey = null,
    ): ThemeRenderPayload {
        return new ThemeRenderPayload(
            definition: [],
            version: [],
            brandProfile: [],
            theme: [
                'tokens' => ThemeDefaultGeneratorService::safeDefaultTokens(),
                'source' => 'safe_default',
            ],
            permissions: $permissions,
            runtimeContext: [
                'status' => 'warning',
                'missing_tables' => $this->missingCoreTables(),
                'organization_public_id' => $context->organizationPublicId,
                'workspace_public_id' => $context->workspacePublicId,
                'module_key' => $moduleKey,
                'theme_key' => $themeKey,
            ],
            warnings: $this->warningsForCoreTables(),
        );
    }
}
