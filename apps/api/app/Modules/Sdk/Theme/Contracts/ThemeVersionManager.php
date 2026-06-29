<?php

namespace App\Modules\Sdk\Theme\Contracts;

interface ThemeVersionManager
{
    /** @return list<\App\Modules\Sdk\Theme\Data\ThemeVersion> */
    public function listVersions(\App\Support\Tenant\TenantContext $context, string $themeKey, ?string $moduleKey = null): array;
}
