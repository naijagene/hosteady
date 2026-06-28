<?php

namespace App\Services\Application;

use App\Modules\Sdk\Application\Contracts\MenuProvider;
use App\Modules\Sdk\Application\Data\NavigationMenu;
use App\Support\Tenant\TenantContext;

class MenuBuilderService implements MenuProvider
{
    public function __construct(
        private readonly NavigationBuilderService $navigationBuilder,
    ) {
    }

    /** @return list<NavigationMenu> */
    public function menus(TenantContext $context): array
    {
        return $this->navigationBuilder->build($context);
    }
}
