<?php

namespace App\Services\Application;

use App\Models\ApplicationRuntime\ApplicationNavigation;
use App\Modules\Sdk\Application\Data\NavigationMenu;
use App\Support\Tenant\TenantContext;

class NavigationRegistryService
{
    /** @return list<NavigationMenu> */
    public function listMenus(TenantContext $context): array
    {
        return app(NavigationBuilderService::class)->build($context);
    }
}
