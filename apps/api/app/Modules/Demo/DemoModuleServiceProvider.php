<?php

namespace App\Modules\Demo;

use App\Providers\HeosModuleServiceProvider;

class DemoModuleServiceProvider extends HeosModuleServiceProvider
{
    protected function moduleClass(): string
    {
        return DemoModule::class;
    }
}
