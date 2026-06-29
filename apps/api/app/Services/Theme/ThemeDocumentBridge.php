<?php

namespace App\Services\Theme;

use App\Support\Tenant\TenantContext;

class ThemeDocumentBridge
{
    /**
     * @param  array<string, mixed>  $documentPayload
     * @return array<string, mixed>
     */
    public function decorateDocumentPayload(TenantContext $context, array $documentPayload, string $themeKey = 'default', ?string $moduleKey = null): array
    {
        $documentPayload['theme'] = app(ThemeApplicationBridge::class)->runtimeTheme($context, $themeKey, $moduleKey);

        return $documentPayload;
    }
}
