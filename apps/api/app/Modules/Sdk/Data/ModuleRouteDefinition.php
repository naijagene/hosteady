<?php

namespace App\Modules\Sdk\Data;

readonly class ModuleRouteDefinition
{
    public function __construct(
        public string $method,
        public string $uri,
        public string $name,
        public ?string $action = null,
    ) {
    }
}
