<?php

namespace App\Services\Theme;

use App\Modules\Sdk\Theme\Contracts\ThemeInheritanceResolver;
use App\Modules\Sdk\Theme\Data\ThemeDefinition;
use App\Modules\Sdk\Theme\Data\ThemeVersion;
use App\Modules\Sdk\Theme\Enums\ThemeInheritanceMode;
use App\Support\Tenant\TenantContext;

class ThemeInheritanceResolverService implements ThemeInheritanceResolver
{
    public const MAX_INHERITANCE_DEPTH = 25;

    public function __construct(
        private readonly ThemeRegistryService $registryService,
        private readonly ThemeTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function assertNotSelfParent(string $themePublicId, ?string $parentThemePublicId): void
    {
        if ($parentThemePublicId !== null && $parentThemePublicId !== '' && $parentThemePublicId === $themePublicId) {
            throw new \App\Modules\Sdk\Theme\Exceptions\ThemeValidationException('Theme cannot inherit from itself.');
        }
    }

    /**
     * @return array{theme: array<string, mixed>, warnings: list<string>}
     */
    public function resolve(TenantContext $context, ThemeDefinition $definition, ?ThemeVersion $version = null): array
    {
        $warnings = [];
        $resolved = $version?->snapshot['tokens'] ?? $definition->tokens;
        if (! is_array($resolved)) {
            $resolved = [];
        }

        if (! $this->tableHealthSupport->isTablePresent('theme_definitions')) {
            return ['theme' => $resolved, 'warnings' => $warnings];
        }

        $mode = $definition->inheritanceMode !== '' ? $definition->inheritanceMode : ThemeInheritanceMode::None->value;
        if ($mode === ThemeInheritanceMode::None->value || $definition->parentThemePublicId === null || $definition->parentThemePublicId === '') {
            return ['theme' => $resolved, 'warnings' => $warnings];
        }

        if ($definition->parentThemePublicId === $definition->publicId) {
            $warnings[] = sprintf('Theme inheritance cycle detected at [%s].', $definition->publicId);

            return ['theme' => $resolved, 'warnings' => $warnings];
        }

        $visited = [$definition->publicId];
        $parentPublicId = $definition->parentThemePublicId;
        $parentTokens = [];
        $depth = 0;

        while ($parentPublicId !== null && $parentPublicId !== '') {
            if (in_array($parentPublicId, $visited, true)) {
                $warnings[] = sprintf('Theme inheritance cycle detected at [%s].', $parentPublicId);
                break;
            }

            $depth++;
            if ($depth > self::MAX_INHERITANCE_DEPTH) {
                $warnings[] = sprintf(
                    'Theme inheritance depth exceeds limit of %d.',
                    self::MAX_INHERITANCE_DEPTH,
                );
                break;
            }

            $visited[] = $parentPublicId;

            try {
                $parentModel = $this->registryService->resolveModelByPublicId(
                    $context->organization->id,
                    $context->workspace?->id,
                    $parentPublicId,
                );
            } catch (\Throwable) {
                $warnings[] = sprintf('Parent theme [%s] was not found.', $parentPublicId);
                break;
            }

            $parent = ThemeMapper::toDefinition($parentModel);
            $parentTokens = is_array($parent->tokens) ? $parent->tokens : [];
            $parentPublicId = $parent->parentThemePublicId;

            if ($parent->inheritanceMode === ThemeInheritanceMode::None->value) {
                break;
            }
        }

        if ($mode === ThemeInheritanceMode::MergeParent->value) {
            return ['theme' => array_replace($parentTokens, $resolved), 'warnings' => $warnings];
        }

        if ($mode === ThemeInheritanceMode::OverrideParent->value) {
            return ['theme' => $resolved !== [] ? $resolved : $parentTokens, 'warnings' => $warnings];
        }

        return ['theme' => $resolved, 'warnings' => $warnings];
    }
}
