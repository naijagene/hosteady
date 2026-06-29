<?php

namespace App\Services\Personalization;

use App\Modules\Sdk\Personalization\Data\PersonalizationRuntimePayload;
use App\Support\Tenant\TenantContext;

class PersonalizationRuntimeComposerService implements \App\Modules\Sdk\Personalization\Contracts\PersonalizationRuntimeComposer
{
    public function __construct(
        private readonly PreferenceService $preferenceService,
        private readonly FavoriteService $favoriteService,
        private readonly RecentActivityService $recentActivityService,
        private readonly ShortcutService $shortcutService,
        private readonly QuickActionService $quickActionService,
        private readonly OnboardingService $onboardingService,
        private readonly PersonalizationThemeBridge $themeBridge,
        private readonly PersonalizationNavigationBridge $navigationBridge,
        private readonly PersonalizationDashboardBridge $dashboardBridge,
        private readonly PersonalizationTableBridge $tableBridge,
        private readonly PersonalizationNotificationBridge $notificationBridge,
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function compose(TenantContext $context): PersonalizationRuntimePayload
    {
        if (! $this->tableHealthSupport->coreTablesPresent()) {
            return $this->tableHealthSupport->emptyRuntimePayloadWithContext($context);
        }

        $preferences = $this->preferenceService->list($context);
        $resolvedPreferences = PersonalizationMapper::resolvePreferences($preferences);

        return new PersonalizationRuntimePayload(
            profile: [],
            preferences: $resolvedPreferences,
            favorites: array_map(fn ($item) => $item->toArray(), $this->favoriteService->list($context)),
            recent: array_map(fn ($item) => $item->toArray(), $this->recentActivityService->list($context)),
            shortcuts: array_map(fn ($item) => $item->toArray(), $this->shortcutService->list($context)),
            quickActions: $this->quickActionService->generate($context),
            onboarding: array_map(fn ($item) => $item->toArray(), $this->onboardingService->list($context)),
            themeOverride: $this->themeBridge->resolve($context, $resolvedPreferences),
            navigationOverrides: $this->navigationBridge->resolve($context, $resolvedPreferences),
            dashboardOverrides: $this->dashboardBridge->resolve($context, $resolvedPreferences),
            tableOverrides: $this->tableBridge->resolve($context, $resolvedPreferences),
            notificationPreferencesReference: $this->notificationBridge->resolve($context, $resolvedPreferences),
            capabilities: [
                'personalization' => true,
                'precedence' => PersonalizationMapper::SCOPE_PRECEDENCE,
            ],
            metadata: [
                'organization_public_id' => $context->organizationPublicId,
                'workspace_public_id' => $context->workspacePublicId,
                'membership_public_id' => $context->membershipPublicId,
                'source' => 'personalization_framework',
            ],
            warnings: [],
        );
    }
}
