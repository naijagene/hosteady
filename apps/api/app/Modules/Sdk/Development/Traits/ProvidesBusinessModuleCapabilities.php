<?php

namespace App\Modules\Sdk\Development\Traits;

use App\Modules\Sdk\Development\Data\BusinessModuleCapabilityDefinition;

trait ProvidesBusinessModuleCapabilities
{
    /**
     * @return list<BusinessModuleCapabilityDefinition>
     */
    public function capabilities(): array
    {
        return $this->manifest()->capabilities;
    }
}
