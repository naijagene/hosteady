<?php

namespace App\Modules\Sdk\Theme\Contracts;

interface ThemeBrandProfileProvider
{
    public function get(\App\Support\Tenant\TenantContext $context, string $themeDefinitionPublicId): ?\App\Modules\Sdk\Theme\Data\BrandProfile;

    public function update(\App\Support\Tenant\TenantContext $context, string $themeDefinitionPublicId, array $profile): \App\Modules\Sdk\Theme\Data\BrandProfile;
}
