<?php

namespace App\Modules\Sdk\Theme\Contracts;

interface ThemePublisher
{
    public function publish(\App\Support\Tenant\TenantContext $context, string $themeKey, ?string $versionPublicId = null, ?string $moduleKey = null): \App\Modules\Sdk\Theme\Data\ThemeDefinition;
}
