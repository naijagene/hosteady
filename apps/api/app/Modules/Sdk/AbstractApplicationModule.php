<?php

namespace App\Modules\Sdk;

use App\Modules\Sdk\Contracts\ApplicationModule;
use App\Modules\Sdk\Contracts\ModuleHealthContext;
use App\Modules\Sdk\Data\ModuleHealthReport;
use App\Modules\Sdk\Data\ModuleManifest;
use App\Modules\Sdk\Data\ModuleNavigationItem;
use App\Modules\Sdk\Data\ModulePermission;
use App\Modules\Sdk\Data\ModuleRouteCollection;
use App\Modules\Sdk\Data\ModuleSettingDefinition;

abstract class AbstractApplicationModule implements ApplicationModule
{
    /**
     * @return list<ModulePermission>
     */
    public function permissions(): array
    {
        return [];
    }

    /**
     * @return list<ModuleSettingDefinition>
     */
    public function settingDefinitions(): array
    {
        return [];
    }

    /**
     * @return list<string>
     */
    public function capabilities(): array
    {
        return [];
    }

    public function dependencies(): array
    {
        return [];
    }

    /**
     * @return list<ModuleNavigationItem>
     */
    public function navigation(): array
    {
        return [];
    }

    public function routes(): ModuleRouteCollection
    {
        return new ModuleRouteCollection;
    }

    public function boot(): void
    {
    }

    public function health(ModuleHealthContext $context): ModuleHealthReport
    {
        return ModuleHealthReport::healthy();
    }

    protected function buildManifest(
        string $moduleUuid,
        string $key,
        string $name,
        string $version,
        bool $isCore,
        bool $bootstrap = true,
        ?string $category = null,
        ?string $icon = null,
        ?string $description = null,
        ?array $runtimeExtensions = null,
    ): ModuleManifest {
        return new ModuleManifest(
            manifestVersion: ModuleManifest::CURRENT_MANIFEST_VERSION,
            moduleUuid: $moduleUuid,
            key: $key,
            name: $name,
            version: $version,
            category: $category,
            icon: $icon,
            description: $description,
            isCore: $isCore,
            bootstrap: $bootstrap,
            capabilities: $this->capabilities(),
            dependencies: $this->dependencies(),
            permissions: $this->permissions(),
            settings: $this->settingDefinitions(),
            navigation: $this->navigation(),
            routes: $this->routes(),
            runtimeExtensions: $runtimeExtensions,
        );
    }
}
