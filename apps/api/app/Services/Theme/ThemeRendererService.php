<?php

namespace App\Services\Theme;

use App\Modules\Sdk\Theme\Contracts\ThemeRenderer;
use App\Modules\Sdk\Theme\Data\ThemeRenderPayload;
use App\Support\Tenant\TenantContext;

class ThemeRendererService implements ThemeRenderer
{
    public function __construct(
        private readonly ThemeRegistryService $registryService,
        private readonly ThemeVersionService $versionService,
        private readonly BrandProfileService $brandProfileService,
        private readonly ThemeInheritanceResolverService $inheritanceResolver,
        private readonly ThemePermissionBridge $permissionBridge,
        private readonly ThemeAuditRecorder $auditRecorder,
        private readonly ThemeTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function render(TenantContext $context, string $themeKey, ?string $moduleKey = null, bool $previewDraft = false): ThemeRenderPayload
    {
        $permissions = $this->permissionBridge->renderPermissions($context);

        if (! $this->tableHealthSupport->coreTablesPresent()) {
            return $this->tableHealthSupport->emptyRenderPayload($context, $permissions, $moduleKey, $themeKey);
        }

        $definition = $this->registryService->findByKey(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey ?? '',
            $themeKey,
        );

        $version = $this->versionService->findPublishedVersion($context, $themeKey, $moduleKey);
        $brandProfile = $this->brandProfileService->get($context, $definition->publicId);
        $resolved = $this->inheritanceResolver->resolve($context, $definition, $version);

        $theme = [
            'tokens' => $resolved['theme'] !== [] ? $resolved['theme'] : ThemeDefaultGeneratorService::safeDefaultTokens(),
            'brand' => $brandProfile?->toArray() ?? [],
            'source' => $resolved['theme'] !== [] ? 'theme_designer' : 'safe_default',
        ];

        $payload = new ThemeRenderPayload(
            definition: $definition->toArray(),
            version: $version?->toArray() ?? [],
            brandProfile: $brandProfile?->toArray() ?? [],
            theme: $theme,
            permissions: $permissions,
            runtimeContext: [
                'organization_public_id' => $context->organizationPublicId,
                'workspace_public_id' => $context->workspacePublicId,
                'membership_public_id' => $context->membershipPublicId,
                'module_key' => $definition->moduleKey,
                'theme_key' => $definition->themeKey,
                'application_public_id' => $definition->applicationPublicId,
            ],
            warnings: $resolved['warnings'],
        );

        $this->auditRecorder->recordRendered($definition->publicId, $context);

        return $payload;
    }
}
