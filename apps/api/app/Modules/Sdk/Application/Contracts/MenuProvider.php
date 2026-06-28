<?php

namespace App\Modules\Sdk\Application\Contracts;

interface MenuProvider
{
    /** @return list<\App\Modules\Sdk\Application\Data\NavigationMenu> */
    public function menus(\App\Support\Tenant\TenantContext $context): array;
}
