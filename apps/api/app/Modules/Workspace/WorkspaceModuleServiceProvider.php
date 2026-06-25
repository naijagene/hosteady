<?php

namespace App\Modules\Workspace;

use App\Providers\HeosModuleServiceProvider;

class WorkspaceModuleServiceProvider extends HeosModuleServiceProvider
{
    protected function moduleClass(): string
    {
        return WorkspaceModule::class;
    }
}
