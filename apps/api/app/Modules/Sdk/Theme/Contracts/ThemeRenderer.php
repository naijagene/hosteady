<?php

namespace App\Modules\Sdk\Theme\Contracts;

interface ThemeRenderer
{
    public function render(\App\Support\Tenant\TenantContext $context, string $themeKey, ?string $moduleKey = null, bool $previewDraft = false): \App\Modules\Sdk\Theme\Data\ThemeRenderPayload;
}
