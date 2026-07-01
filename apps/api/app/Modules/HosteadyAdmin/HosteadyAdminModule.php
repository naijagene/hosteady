<?php

namespace App\Modules\HosteadyAdmin;

use App\Modules\Sdk\AbstractApplicationModule;
use App\Modules\Sdk\Contracts\ModuleRuntimeContext;
use App\Modules\Sdk\Data\ModuleDependency;
use App\Modules\Sdk\Data\ModuleManifest;
use App\Modules\Sdk\Runtime\RuntimeContribution;

class HosteadyAdminModule extends AbstractApplicationModule
{
    public const MODULE_UUID = '01900000-0000-7000-8000-000000000004';

    public function key(): string
    {
        return 'hosteady-admin';
    }

    public function name(): string
    {
        return 'Hosteady Admin';
    }

    public function version(): string
    {
        return '0.1.0-alpha';
    }

    /**
     * @return list<string>
     */
    public function capabilities(): array
    {
        return ['administration', 'reporting', 'notifications', 'search'];
    }

    public function dependencies(): array
    {
        return [
            new ModuleDependency('core'),
            new ModuleDependency('workspace'),
        ];
    }

    public function manifest(): ModuleManifest
    {
        return $this->buildManifest(
            moduleUuid: self::MODULE_UUID,
            key: $this->key(),
            name: $this->name(),
            version: $this->version(),
            isCore: false,
            bootstrap: false,
            category: 'platform',
            description: 'Reference administration application for HEOS platform operations.',
        );
    }

    public function contributeRuntime(ModuleRuntimeContext $context): RuntimeContribution
    {
        return new RuntimeContribution(
            moduleKey: $this->key(),
            priority: 20,
            capabilities: ['hosteady.admin'],
            featureFlags: ['hosteady.admin.enabled' => true],
            runtimeMetadata: ['hosteady_admin' => ['version' => $this->version(), 'enabled' => true]],
            diagnostics: [[
                'module_key' => $this->key(),
                'status' => 'healthy',
            ]],
        );
    }
}
