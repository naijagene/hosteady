<?php

namespace App\Modules\Sdk\Development\Contracts;

use App\Modules\Sdk\Development\Data\BusinessModuleRouteDefinition;

interface BusinessModuleRouteProvider
{
    public function moduleKey(): string;

    /**
     * @return list<BusinessModuleRouteDefinition>
     */
    public function routes(): array;
}
