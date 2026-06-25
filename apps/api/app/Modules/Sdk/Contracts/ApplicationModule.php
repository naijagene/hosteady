<?php

namespace App\Modules\Sdk\Contracts;

use App\Modules\Sdk\Data\ModuleDependency;
use App\Modules\Sdk\Data\ModuleHealthReport;
use App\Modules\Sdk\Data\ModuleManifest;
use App\Modules\Sdk\Data\ModuleNavigationItem;
use App\Modules\Sdk\Data\ModulePermission;
use App\Modules\Sdk\Data\ModuleRouteCollection;
use App\Modules\Sdk\Data\ModuleSettingDefinition;

interface ApplicationModule
{
    public function key(): string;

    public function name(): string;

    public function version(): string;

    public function manifest(): ModuleManifest;

    /**
     * @return list<ModulePermission>
     */
    public function permissions(): array;

    /**
     * @return list<ModuleSettingDefinition>
     */
    public function settingDefinitions(): array;

    /**
     * @return list<string>
     */
    public function capabilities(): array;

    /**
     * @return list<ModuleDependency>
     */
    public function dependencies(): array;

    /**
     * @return list<ModuleNavigationItem>
     */
    public function navigation(): array;

    public function routes(): ModuleRouteCollection;

    public function boot(): void;

    public function health(ModuleHealthContext $context): ModuleHealthReport;
}
