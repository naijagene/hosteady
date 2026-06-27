<?php

namespace App\Modules\Sdk\Development\Traits;

use App\Modules\Sdk\Development\Data\BusinessModulePermissionDefinition;

trait ProvidesBusinessModulePermissions
{
    /**
     * @return list<BusinessModulePermissionDefinition>
     */
    public function permissions(): array
    {
        return $this->manifest()->permissions;
    }
}
