<?php

namespace App\Services\Theme;

use App\Modules\Sdk\Theme\Data\ThemeDefinition;
use App\Support\Tenant\TenantContext;

class ThemeDefinitionService
{
    public function __construct(
        private readonly ThemeRegistryService $registryService,
        private readonly ThemeAuditRecorder $auditRecorder,
        private readonly ThemeInheritanceResolverService $inheritanceResolver,
        private readonly ThemeTableHealthSupport $tableHealthSupport,
    ) {
    }

    /** @return list<ThemeDefinition> */
    public function list(TenantContext $context): array
    {
        return $this->registryService->list($context->organization->id, $context->workspace?->id);
    }

    /**
     * @param  ThemeDefinition|array<string, mixed>  $definition
     */
    public function create(TenantContext $context, mixed $definition): ThemeDefinition
    {
        if ($definition instanceof ThemeDefinition) {
            $this->inheritanceResolver->assertNotSelfParent($definition->publicId, $definition->parentThemePublicId);
        } elseif (is_array($definition)) {
            $this->inheritanceResolver->assertNotSelfParent(
                (string) ($definition['public_id'] ?? $definition['publicId'] ?? ''),
                isset($definition['parent_theme_public_id']) ? (string) $definition['parent_theme_public_id'] : (isset($definition['parentThemePublicId']) ? (string) $definition['parentThemePublicId'] : null),
            );
        }

        return $this->registryService->registerFromSource(
            $context->organization->id,
            $context->workspace?->id,
            null,
            $definition,
        );
    }

    public function find(TenantContext $context, string $moduleKey, string $themeKey): ThemeDefinition
    {
        return $this->registryService->findByKey($context->organization->id, $context->workspace?->id, $moduleKey, $themeKey);
    }

    public function findByPublicId(TenantContext $context, string $publicId): ThemeDefinition
    {
        return ThemeMapper::toDefinition(
            $this->registryService->resolveModelByPublicId($context->organization->id, $context->workspace?->id, $publicId),
        );
    }

    public function update(TenantContext $context, ThemeDefinition $definition): ThemeDefinition
    {
        $this->inheritanceResolver->assertNotSelfParent($definition->publicId, $definition->parentThemePublicId);

        if (! $this->tableHealthSupport->isTablePresent('theme_definitions')) {
            throw new \App\Modules\Sdk\Theme\Exceptions\ThemeValidationException('Theme definitions table is not available.');
        }

        $model = $this->registryService->resolveModelByPublicId(
            $context->organization->id,
            $context->workspace?->id,
            $definition->publicId,
        );

        $model->fill([
            'module_key' => $definition->moduleKey,
            'theme_key' => $definition->themeKey,
            'name' => $definition->name,
            'description' => $definition->description,
            'status' => $definition->status,
            'scope' => $definition->scope,
            'inheritance_mode' => $definition->inheritanceMode,
            'parent_theme_id' => ThemeMapper::resolveThemeId($definition->parentThemePublicId),
            'tokens_json' => $definition->tokens,
            'metadata' => $definition->metadata,
            'application_id' => ThemeMapper::resolveApplicationId($definition->applicationPublicId),
        ]);
        $model->save();

        $updated = ThemeMapper::toDefinition($model->fresh());
        $this->auditRecorder->recordDefinitionUpdated($updated);

        return $updated;
    }
}
