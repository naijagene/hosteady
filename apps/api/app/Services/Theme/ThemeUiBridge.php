<?php

namespace App\Services\Theme;

use App\Support\Tenant\TenantContext;

class ThemeUiBridge
{
    /**
     * @param  array<string, mixed>  $pageThemeOverride
     * @return array{theme: array<string, mixed>, warnings: list<string>}
     */
    public function resolveForPage(
        TenantContext $context,
        ?string $moduleKey,
        ?string $pageKey,
        array $pageThemeOverride = [],
    ): array {
        $moduleKey ??= '';
        $themeKey = $pageKey !== null && $pageKey !== '' ? $pageKey : 'default';

        try {
            $rendered = app(ThemeRendererService::class)->render($context, $themeKey, $moduleKey);
            $resolvedTheme = $rendered->theme;
            $warnings = $rendered->warnings;
        } catch (\Throwable) {
            $resolvedTheme = [
                'tokens' => ThemeDefaultGeneratorService::safeDefaultTokens(),
                'source' => 'safe_default',
            ];
            $warnings = [];
        }

        if ($pageThemeOverride !== []) {
            $resolvedTheme = array_replace_recursive($resolvedTheme, $pageThemeOverride);
            $resolvedTheme['source'] = 'theme_designer+page_override';
        }

        return [
            'theme' => $resolvedTheme,
            'warnings' => $warnings,
        ];
    }
}
