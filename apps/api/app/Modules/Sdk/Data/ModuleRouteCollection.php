<?php

namespace App\Modules\Sdk\Data;

readonly class ModuleRouteCollection
{
    /**
     * @param  list<ModuleRouteDefinition>  $routes
     */
    public function __construct(
        public array $routes = [],
    ) {
    }
}
