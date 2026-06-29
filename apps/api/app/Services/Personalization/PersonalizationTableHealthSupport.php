<?php

namespace App\Services\Personalization;

use App\Modules\Sdk\Personalization\Data\PersonalizationHealthReport;
use App\Modules\Sdk\Personalization\Data\PersonalizationRuntimePayload;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;

class PersonalizationTableHealthSupport
{
    /** @var list<string> */
    public const CORE_TABLES = [
        'personalization_profiles',
        'personalization_preferences',
        'personalization_favorites',
        'personalization_recent_items',
        'personalization_shortcuts',
        'personalization_onboarding_states',
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

    public function emptyHealthReport(): PersonalizationHealthReport
    {
        $missing = $this->missingCoreTables();

        return new PersonalizationHealthReport(
            enabled: (bool) config('heos.enterprise.personalization.enabled', true),
            healthy: $missing === [],
            status: $missing === [] ? 'ok' : 'warning',
            profiles: 0,
            preferences: 0,
            favorites: 0,
            recentItems: 0,
            shortcuts: 0,
            onboardingStates: 0,
            warnings: $this->warningsForCoreTables(),
            missingTables: $missing,
            statistics: [],
        );
    }

    public function emptyRuntimePayload(): PersonalizationRuntimePayload
    {
        $missing = $this->missingCoreTables();

        return new PersonalizationRuntimePayload(
            profile: [],
            preferences: [],
            favorites: [],
            recent: [],
            shortcuts: [],
            quickActions: [],
            onboarding: [],
            themeOverride: [],
            navigationOverrides: [],
            dashboardOverrides: [],
            tableOverrides: [],
            notificationPreferencesReference: [],
            capabilities: [
                'personalization' => true,
                'precedence' => PersonalizationMapper::SCOPE_PRECEDENCE,
            ],
            metadata: [
                'source' => 'safe_default',
                'status' => 'warning',
                'missing_tables' => $missing,
            ],
            warnings: $this->warningsForCoreTables(),
        );
    }

    public function emptyRuntimePayloadWithContext(\App\Support\Tenant\TenantContext $context): PersonalizationRuntimePayload
    {
        $payload = $this->emptyRuntimePayload();

        return new PersonalizationRuntimePayload(
            profile: $payload->profile,
            preferences: $payload->preferences,
            favorites: $payload->favorites,
            recent: $payload->recent,
            shortcuts: $payload->shortcuts,
            quickActions: $payload->quickActions,
            onboarding: $payload->onboarding,
            themeOverride: $payload->themeOverride,
            navigationOverrides: $payload->navigationOverrides,
            dashboardOverrides: $payload->dashboardOverrides,
            tableOverrides: $payload->tableOverrides,
            notificationPreferencesReference: $payload->notificationPreferencesReference,
            capabilities: $payload->capabilities,
            metadata: array_merge($payload->metadata, [
                'organization_public_id' => $context->organizationPublicId,
                'workspace_public_id' => $context->workspacePublicId,
                'membership_public_id' => $context->membershipPublicId,
            ]),
            warnings: $payload->warnings,
        );
    }
}
