<?php

namespace App\Modules\Sdk\Contracts;

use App\Modules\Sdk\Data\ModuleSyncOptions;
use App\Modules\Sdk\Data\ModuleSyncResult;

interface ModuleSyncPort
{
    public function sync(ModuleRegistryReader $registry, ModuleSyncOptions $options): ModuleSyncResult;
}
