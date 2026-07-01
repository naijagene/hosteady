<?php

namespace App\Modules\HosteadyAdmin;

use App\Providers\HeosModuleServiceProvider;

class HosteadyAdminModuleServiceProvider extends HeosModuleServiceProvider
{
    protected function moduleClass(): string
    {
        return HosteadyAdminModule::class;
    }
}
