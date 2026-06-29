<?php

namespace App\Services\Theme;

use App\Modules\Sdk\Theme\Data\BrandProfile;
use App\Modules\Sdk\Theme\Data\ThemeDefinition;
use App\Modules\Sdk\Theme\Data\ThemeHealthReport;
use App\Modules\Sdk\Theme\Data\ThemeRenderPayload;
use App\Modules\Sdk\Theme\Data\ThemeStatistics;
use App\Modules\Sdk\Theme\Data\ThemeVersion;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ThemeDevelopmentService
{
    public function __construct(
        private readonly ThemeDefinitionService $definitionService,
        private readonly BrandProfileService $brandProfileService,
        private readonly ThemeVersionService $versionService,
        private readonly ThemeRendererService $rendererService,
        private readonly ThemePublisherService $publisherService,
        private readonly ThemeHealthService $healthService,
        private readonly ThemeStatisticsService $statisticsService,
        private readonly ThemePermissionBridge $permissionBridge,
        private readonly ThemeRegistryService $registryService,
        private readonly ThemeTableHealthSupport $tableHealthSupport,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
    ) {
    }

    /** @return list<ThemeDefinition> */
    public function listDefinitions(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->definitionService->list($context);
    }

    /**
     * @param  ThemeDefinition|array<string, mixed>  $definition
     */
    public function registerDefinition(TenantContext $context, mixed $definition): ThemeDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->definitionService->create($context, $definition);
    }

    public function findDefinitionByPublicId(TenantContext $context, string $publicId): ThemeDefinition
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->definitionService->findByPublicId($context, $publicId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDefinitionByPublicId(TenantContext $context, string $publicId, array $data): ThemeDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        $existing = $this->definitionService->findByPublicId($context, $publicId);

        return $this->definitionService->update(
            $context,
            ThemeDefinition::fromArray(array_merge($existing->toArray(), $data)),
        );
    }

    /** @return list<BrandProfile> */
    public function listBrandProfiles(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->brandProfileService->list($context);
    }

    public function findBrandProfileByPublicId(TenantContext $context, string $publicId): BrandProfile
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->brandProfileService->findByPublicId($context, $publicId);
    }

    public function updateBrandProfile(TenantContext $context, string $themeDefinitionPublicId, array $profile): BrandProfile
    {
        $this->requireCapability($context);
        $this->assertBrand($context);

        return $this->brandProfileService->update($context, $themeDefinitionPublicId, $profile);
    }

    public function createThemeVersion(TenantContext $context, string $themeDefinitionPublicId, array $snapshot = [], ?string $changeSummary = null): ThemeVersion
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        if ($snapshot === []) {
            $definition = $this->definitionService->findByPublicId($context, $themeDefinitionPublicId);
            $snapshot = [
                'tokens' => $definition->tokens,
                'brand_profile' => $this->brandProfileService->get($context, $themeDefinitionPublicId)?->toArray() ?? [],
            ];
        }

        return $this->versionService->createDraft($context, $themeDefinitionPublicId, $snapshot, $changeSummary);
    }

    /** @return list<ThemeVersion> */
    public function listVersionsForDefinition(TenantContext $context, string $themeDefinitionPublicId): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $definition = $this->definitionService->findByPublicId($context, $themeDefinitionPublicId);

        return $this->versionService->listVersions($context, $definition->themeKey, $definition->moduleKey);
    }

    public function publishDefinition(TenantContext $context, string $themeDefinitionPublicId, ?string $versionPublicId = null): ThemeDefinition
    {
        $this->requireCapability($context);
        $this->assertPublish($context);

        $definition = $this->definitionService->findByPublicId($context, $themeDefinitionPublicId);

        return $this->publisherService->publish($context, $definition->themeKey, $versionPublicId, $definition->moduleKey);
    }

    public function renderDefinition(TenantContext $context, string $themeDefinitionPublicId): ThemeRenderPayload
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $definition = $this->definitionService->findByPublicId($context, $themeDefinitionPublicId);

        return $this->rendererService->render($context, $definition->themeKey, $definition->moduleKey);
    }

    public function renderTheme(TenantContext $context, string $themeKey = 'default', ?string $moduleKey = null): ThemeRenderPayload
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        if (! $this->tableHealthSupport->coreTablesPresent()) {
            return $this->tableHealthSupport->emptyRenderPayload(
                $context,
                $this->permissionBridge->renderPermissions($context),
                $moduleKey,
                $themeKey,
            );
        }

        try {
            return $this->rendererService->render($context, $themeKey, $moduleKey);
        } catch (\Throwable) {
            return $this->tableHealthSupport->emptyRenderPayload(
                $context,
                $this->permissionBridge->renderPermissions($context),
                $moduleKey,
                $themeKey,
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function composeRuntime(TenantContext $context, string $themeKey = 'default', ?string $moduleKey = null): array
    {
        $payload = $this->renderTheme($context, $themeKey, $moduleKey);

        return [
            'definition' => $payload->definition,
            'version' => $payload->version,
            'brand_profile' => $payload->brandProfile,
            'theme' => $payload->theme,
            'runtime_context' => $payload->runtimeContext,
            'permissions' => $payload->permissions,
            'warnings' => $payload->warnings,
            'source' => 'theme_framework',
        ];
    }

    public function health(TenantContext $context): ThemeHealthReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->healthService->health($context);
    }

    public function statistics(TenantContext $context): ThemeStatistics
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->statisticsService->statisticsForScope($context->organization, $context->workspace);
    }

    private function requireCapability(TenantContext $context): void
    {
        if (! (bool) config('heos.enterprise.themes.enabled', true)) {
            throw new HttpException(503, 'Theme framework is disabled.');
        }

        $this->runtimeBridge->requireCapability($context, 'themes');
    }

    private function assertRead(TenantContext $context): void
    {
        if (! $this->permissionBridge->canRead($context)) {
            throw new HttpException(403, 'You do not have permission to read themes.');
        }
    }

    private function assertManage(TenantContext $context): void
    {
        if (! $this->permissionBridge->canManage($context)) {
            throw new HttpException(403, 'You do not have permission to manage themes.');
        }
    }

    private function assertPublish(TenantContext $context): void
    {
        if (! $this->permissionBridge->canPublish($context)) {
            throw new HttpException(403, 'You do not have permission to publish themes.');
        }
    }

    private function assertBrand(TenantContext $context): void
    {
        if (! $this->permissionBridge->canManageBrand($context)) {
            throw new HttpException(403, 'You do not have permission to manage brand profiles.');
        }
    }
}
