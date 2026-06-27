<?php

namespace App\Modules\Sdk\Development\Contracts;

use App\Modules\Sdk\Development\Data\BusinessModuleCapabilityDefinition;
use App\Modules\Sdk\Development\Data\BusinessModuleManifest;
use App\Modules\Sdk\Development\Data\BusinessModulePermissionDefinition;
use App\Modules\Sdk\Development\Data\BusinessModuleRouteDefinition;

interface BusinessModule
{
    public function key(): string;

    public function name(): string;

    public function version(): string;

    public function manifest(): BusinessModuleManifest;

    /**
     * @return list<BusinessModuleCapabilityDefinition>
     */
    public function capabilities(): array;

    /**
     * @return list<BusinessModulePermissionDefinition>
     */
    public function permissions(): array;

    /**
     * @return list<BusinessModuleRouteDefinition>
     */
    public function routes(): array;

    /**
     * @return list<string>
     */
    public function dependencies(): array;

    public function boot(): void;
}
