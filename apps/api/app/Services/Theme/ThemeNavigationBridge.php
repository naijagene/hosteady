<?php

namespace App\Services\Theme;

use App\Support\Tenant\TenantContext;

class ThemeNavigationBridge
{
    /**
     * @param  array<string, mixed>  $navigationPayload
     * @return array<string, mixed>
     */
    public function decorateRuntimePayload(TenantContext $context, array $navigationPayload, string $themeKey = 'default', ?string $moduleKey = null): array
    {
        $navigationPayload['theme'] = app(ThemeApplicationBridge::class)->runtimeTheme($context, $themeKey, $moduleKey);

        return $navigationPayload;
    }
}
