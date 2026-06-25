<?php

namespace App\Modules\Demo;

use App\Modules\Sdk\AbstractApplicationModule;
use App\Modules\Sdk\Contracts\ModuleRuntimeContext;
use App\Modules\Sdk\Data\ModuleDependency;
use App\Modules\Sdk\Data\ModuleManifest;
use App\Modules\Sdk\Data\ModuleSettingDefinition;
use App\Modules\Sdk\Runtime\RuntimeContribution;

class DemoModule extends AbstractApplicationModule
{
    public const MODULE_UUID = '01900000-0000-7000-8000-000000000003';

    public function key(): string
    {
        return 'demo';
    }

    public function name(): string
    {
        return 'Demo Application';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    /**
     * @return list<string>
     */
    public function capabilities(): array
    {
        return ['notifications', 'reporting'];
    }

    public function dependencies(): array
    {
        return [
            new ModuleDependency('core'),
            new ModuleDependency('workspace'),
        ];
    }

    /**
     * @return list<ModuleSettingDefinition>
     */
    public function settingDefinitions(): array
    {
        return [
            new ModuleSettingDefinition(
                settingKey: 'feature.enabled',
                label: 'Feature Enabled',
                description: 'Toggle the demo feature module.',
                settingType: 'boolean',
                defaultValue: false,
                category: 'features',
                sortOrder: 10,
            ),
            new ModuleSettingDefinition(
                settingKey: 'notification.email',
                label: 'Notification Email',
                description: 'Email address used for demo notifications.',
                settingType: 'string',
                defaultValue: null,
                category: 'notifications',
                sortOrder: 20,
                validationRules: [
                    'pattern' => '^[^@]+@[^@]+\.[^@]+$',
                ],
            ),
            new ModuleSettingDefinition(
                settingKey: 'secret.token',
                label: 'Secret Token',
                description: 'Sensitive token used by the demo integration.',
                settingType: 'string',
                defaultValue: null,
                isSensitive: true,
                category: 'secrets',
                sortOrder: 30,
                validationRules: [
                    'min_length' => 8,
                ],
            ),
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
        );
    }

    public function contributeRuntime(ModuleRuntimeContext $context): RuntimeContribution
    {
        return new RuntimeContribution(
            moduleKey: $this->key(),
            priority: 10,
            capabilities: ['demo.runtime'],
            navigation: [[
                'module_key' => $this->key(),
                'label' => 'Demo',
                'route_name' => 'heos.demo.home',
            ]],
            featureFlags: ['demo.preview' => true],
            runtimeMetadata: ['demo' => ['enabled' => true]],
            diagnostics: [[
                'module_key' => $this->key(),
                'status' => 'healthy',
            ]],
            settingsMetadata: ['feature.enabled' => ['source' => 'module']],
        );
    }
}
