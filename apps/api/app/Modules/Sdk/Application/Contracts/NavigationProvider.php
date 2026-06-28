<?php

namespace App\Modules\Sdk\Application\Contracts;

interface NavigationProvider
{
    /** @return list<\App\Modules\Sdk\Application\Data\NavigationMenu> */
    public function navigation(\App\Support\Tenant\TenantContext $context): array;
}
