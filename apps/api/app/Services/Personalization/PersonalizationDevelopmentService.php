<?php

namespace App\Services\Personalization;

use App\Modules\Sdk\Personalization\Data\FavoriteItem;
use App\Modules\Sdk\Personalization\Data\OnboardingState;
use App\Modules\Sdk\Personalization\Data\PersonalizationHealthReport;
use App\Modules\Sdk\Personalization\Data\PersonalizationRuntimePayload;
use App\Modules\Sdk\Personalization\Data\PreferenceItem;
use App\Modules\Sdk\Personalization\Data\ShortcutItem;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PersonalizationDevelopmentService
{
    public function __construct(
        private readonly PersonalizationRuntimeComposerService $runtimeComposerService,
        private readonly PersonalizationHealthService $healthService,
        private readonly PersonalizationStatisticsService $statisticsService,
        private readonly PersonalizationPermissionBridge $permissionBridge,
        private readonly PreferenceService $preferenceService,
        private readonly FavoriteService $favoriteService,
        private readonly RecentActivityService $recentActivityService,
        private readonly ShortcutService $shortcutService,
        private readonly OnboardingService $onboardingService,
        private readonly PersonalizationRegistryService $registryService,
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
    ) {
    }

    public function runtime(TenantContext $context): PersonalizationRuntimePayload
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        if ($this->tableHealthSupport->coreTablesPresent()) {
            $this->registryService->ensureProfile($context);
        }

        try {
            return $this->runtimeComposerService->compose($context);
        } catch (\Throwable) {
            return $this->tableHealthSupport->emptyRuntimePayloadWithContext($context);
        }
    }

    /** @return list<PreferenceItem> */
    public function listPreferences(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->preferenceService->list($context);
    }

    /**
     * @param  array<string, mixed>  $preferences
     * @return list<PreferenceItem>
     */
    public function patchPreferences(TenantContext $context, array $preferences): array
    {
        $this->requireCapability($context);
        $this->assertWrite($context);

        return $this->preferenceService->patchMany($context, $preferences);
    }

    /** @return list<FavoriteItem> */
    public function listFavorites(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->favoriteService->list($context);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function addFavorite(TenantContext $context, array $payload): FavoriteItem
    {
        $this->requireCapability($context);
        $this->assertWrite($context);

        return $this->favoriteService->add(
            $context,
            (string) ($payload['subject_type'] ?? $payload['favorite_type'] ?? 'custom'),
            (string) ($payload['subject_public_id'] ?? ''),
            isset($payload['label']) ? (string) $payload['label'] : null,
        );
    }

    public function removeFavorite(TenantContext $context, string $favoritePublicId): void
    {
        $this->requireCapability($context);
        $this->assertWrite($context);

        $this->favoriteService->remove($context, $favoritePublicId);
    }

    /** @return list<\App\Modules\Sdk\Personalization\Data\RecentItem> */
    public function listRecent(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->recentActivityService->list($context);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordRecent(TenantContext $context, array $payload): \App\Modules\Sdk\Personalization\Data\RecentItem
    {
        $this->requireCapability($context);
        $this->assertWrite($context);

        return $this->recentActivityService->record(
            $context,
            (string) ($payload['subject_type'] ?? $payload['item_type'] ?? 'custom'),
            (string) ($payload['subject_public_id'] ?? ''),
            isset($payload['title']) ? (string) $payload['title'] : (isset($payload['label']) ? (string) $payload['label'] : null),
        );
    }

    /** @return list<ShortcutItem> */
    public function listShortcuts(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->shortcutService->list($context);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createShortcut(TenantContext $context, array $payload): ShortcutItem
    {
        $this->requireCapability($context);
        $this->assertWrite($context);

        return $this->shortcutService->create($context, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateShortcut(TenantContext $context, string $shortcutPublicId, array $payload): ShortcutItem
    {
        $this->requireCapability($context);
        $this->assertWrite($context);

        return $this->shortcutService->update($context, $shortcutPublicId, $payload);
    }

    public function deleteShortcut(TenantContext $context, string $shortcutPublicId): void
    {
        $this->requireCapability($context);
        $this->assertWrite($context);

        $this->shortcutService->delete($context, $shortcutPublicId);
    }

    /** @return list<OnboardingState> */
    public function onboarding(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->onboardingService->list($context);
    }

    public function onboardingStart(TenantContext $context, string $flowKey): OnboardingState
    {
        $this->requireCapability($context);
        $this->assertWrite($context);

        return $this->onboardingService->start($context, $flowKey);
    }

    public function onboardingStep(TenantContext $context, string $flowKey, string $step): OnboardingState
    {
        $this->requireCapability($context);
        $this->assertWrite($context);

        return $this->onboardingService->step($context, $flowKey, $step);
    }

    public function onboardingComplete(TenantContext $context, string $flowKey): OnboardingState
    {
        $this->requireCapability($context);
        $this->assertWrite($context);

        return $this->onboardingService->complete($context, $flowKey);
    }

    public function onboardingReset(TenantContext $context, string $flowKey): OnboardingState
    {
        $this->requireCapability($context);
        $this->assertWrite($context);

        return $this->onboardingService->reset($context, $flowKey);
    }

    /**
     * @return array<string, int>
     */
    public function statistics(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->statisticsService->statisticsForScope($context->organization, $context->workspace);
    }

    public function health(TenantContext $context): PersonalizationHealthReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->healthService->health($context);
    }

    private function requireCapability(TenantContext $context): void
    {
        if (! (bool) config('heos.enterprise.personalization.enabled', true)) {
            throw new HttpException(503, 'Personalization framework is disabled.');
        }

        $this->runtimeBridge->requireCapability($context, 'personalization');
    }

    private function assertRead(TenantContext $context): void
    {
        if (! $this->permissionBridge->canRead($context)) {
            throw new HttpException(403, 'You do not have permission to read personalization.');
        }
    }

    private function assertWrite(TenantContext $context): void
    {
        if (! $this->permissionBridge->canWrite($context)) {
            throw new HttpException(403, 'You do not have permission to write personalization.');
        }
    }
}
