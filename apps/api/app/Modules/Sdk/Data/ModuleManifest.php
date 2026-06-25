<?php

namespace App\Modules\Sdk\Data;

readonly class ModuleManifest
{
    public const CURRENT_MANIFEST_VERSION = 1;

    /**
     * @param  list<string>  $capabilities
     * @param  list<ModuleDependency>  $dependencies
     * @param  list<ModulePermission>  $permissions
     * @param  list<ModuleSettingDefinition>  $settings
     * @param  list<ModuleNavigationItem>  $navigation
     * @param  array<string, mixed>|null  $runtimeExtensions
     */
    public function __construct(
        public int $manifestVersion,
        public string $moduleUuid,
        public string $key,
        public string $name,
        public string $version,
        public ?string $category,
        public ?string $icon,
        public ?string $description,
        public bool $isCore,
        public bool $bootstrap,
        public array $capabilities,
        public array $dependencies,
        public array $permissions,
        public array $settings,
        public array $navigation,
        public ModuleRouteCollection $routes,
        public ?array $runtimeExtensions = null,
    ) {
    }
}
