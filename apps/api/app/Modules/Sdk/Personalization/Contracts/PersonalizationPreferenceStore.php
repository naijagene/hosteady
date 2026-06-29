<?php

namespace App\Modules\Sdk\Personalization\Contracts;

use App\Modules\Sdk\Personalization\Data\PreferenceItem;
use App\Support\Tenant\TenantContext;

interface PersonalizationPreferenceStore
{
    /** @return list<PreferenceItem> */
    public function list(TenantContext $context, ?string $scope = null): array;

    public function upsert(TenantContext $context, string $key, string $type, mixed $value, string $scope = 'membership'): PreferenceItem;
}
