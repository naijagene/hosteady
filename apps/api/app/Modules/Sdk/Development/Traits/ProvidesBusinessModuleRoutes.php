<?php

namespace App\Modules\Sdk\Development\Traits;

use App\Modules\Sdk\Development\Data\BusinessModuleRouteDefinition;

trait ProvidesBusinessModuleRoutes
{
    /**
     * @return list<BusinessModuleRouteDefinition>
     */
    public function routes(): array
    {
        return $this->manifest()->routes;
    }
}
