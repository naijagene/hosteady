<?php

namespace App\Modules\Workspace;

use App\Modules\Sdk\AbstractApplicationModule;
use App\Modules\Sdk\Data\ModuleManifest;

class WorkspaceModule extends AbstractApplicationModule
{
    public const MODULE_UUID = '01900000-0000-7000-8000-000000000002';

    public function key(): string
    {
        return 'workspace';
    }

    public function name(): string
    {
        return 'Workspace Module';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function manifest(): ModuleManifest
    {
        return $this->buildManifest(
            moduleUuid: self::MODULE_UUID,
            key: $this->key(),
            name: $this->name(),
            version: $this->version(),
            isCore: true,
            category: 'platform',
        );
    }
}
