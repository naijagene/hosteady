<?php

namespace App\Modules\Sdk\Navigation\Contracts;

interface NavigationPersonalizationProvider
{
    public function get(\App\Support\Tenant\TenantContext $context, string $navigationDefinitionPublicId): \App\Modules\Sdk\Navigation\Data\NavigationPersonalization;

    public function update(\App\Support\Tenant\TenantContext $context, string $navigationDefinitionPublicId, array $personalization): \App\Modules\Sdk\Navigation\Data\NavigationPersonalization;
}
