<?php

namespace App\Services\Module\Data;

use App\Modules\Sdk\Contracts\ModuleHealthContext;

class PlatformModuleHealthContext implements ModuleHealthContext
{
    public function organizationPublicId(): string
    {
        return 'platform';
    }

    public function workspacePublicId(): ?string
    {
        return null;
    }
}
