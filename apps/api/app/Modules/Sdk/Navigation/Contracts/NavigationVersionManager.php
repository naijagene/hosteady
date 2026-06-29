<?php

namespace App\Modules\Sdk\Navigation\Contracts;

interface NavigationVersionManager
{
    /** @return list<\App\Modules\Sdk\Navigation\Data\NavigationVersion> */
    public function listVersions(\App\Support\Tenant\TenantContext $context, string $navigationKey, ?string $moduleKey = null): array;

    public function findVersion(\App\Support\Tenant\TenantContext $context, string $versionPublicId): \App\Modules\Sdk\Navigation\Data\NavigationVersion;
}
