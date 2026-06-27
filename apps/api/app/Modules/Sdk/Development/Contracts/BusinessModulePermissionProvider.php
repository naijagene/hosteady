<?php

namespace App\Modules\Sdk\Development\Contracts;

use App\Modules\Sdk\Development\Data\BusinessModulePermissionDefinition;

interface BusinessModulePermissionProvider
{
    public function moduleKey(): string;

    /**
     * @return list<BusinessModulePermissionDefinition>
     */
    public function permissions(): array;
}
