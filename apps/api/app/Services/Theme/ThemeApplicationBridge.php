<?php

namespace App\Services\Theme;

use App\Support\Tenant\TenantContext;

class ThemeApplicationBridge
{
    /**
     * @return array<string, mixed>
     */
    public function runtimeTheme(TenantContext $context, string $themeKey = 'default', ?string $moduleKey = null): array
    {
        try {
            return app(ThemeRendererService::class)
                ->render($context, $themeKey, $moduleKey)
                ->theme;
        } catch (\Throwable) {
            return [
                'tokens' => ThemeDefaultGeneratorService::safeDefaultTokens(),
                'source' => 'safe_default',
            ];
        }
    }
}
