<?php

namespace App\Modules\Core;

use App\Providers\HeosModuleServiceProvider;

class CoreModuleServiceProvider extends HeosModuleServiceProvider
{
    protected function moduleClass(): string
    {
        return CoreModule::class;
    }
}
