<?php

namespace App\Modules\Sdk\Navigation\Contracts;

interface NavigationPublisher
{
    public function publish(\App\Support\Tenant\TenantContext $context, string $navigationKey, ?string $versionPublicId = null, ?string $moduleKey = null): \App\Modules\Sdk\Navigation\Data\NavigationDefinition;
}
