<?php

namespace App\Services\Application;

use App\Modules\Sdk\Application\Data\ApplicationManifest;
use App\Modules\Sdk\Application\Data\NavigationMenu;
use App\Modules\Sdk\Development\BusinessModuleBase;
use App\Services\Module\Development\BusinessModuleRegistryService;
use App\Support\Tenant\TenantContext;

class ApplicationDiscoveryService
{
    public function __construct(
        private readonly BusinessModuleRegistryService $moduleRegistry,
    ) {
    }

    /** @return list<NavigationMenu> */
    public function discoverNavigation(TenantContext $context): array
    {
        $menus = [];

        foreach ($this->resolvedModules() as $module) {
            if (! method_exists($module, 'navigation')) {
                continue;
            }

            $items = $module->navigation();
            if (! is_array($items) || $items === []) {
                continue;
            }

            $menus[] = new NavigationMenu(
                menuKey: $module->moduleKey(),
                label: $module->name(),
                groups: [[
                    'group_key' => $module->moduleKey(),
                    'label' => $module->name(),
                    'sort_order' => 0,
                    'items' => $items,
                    'metadata' => [],
                ]],
                metadata: ['module_key' => $module->moduleKey()],
            );
        }

        return $menus;
    }

    /** @return array<string, mixed> */
    public function discoverModuleContributions(TenantContext $context): array
    {
        $contributions = [
            'forms' => [],
            'tables' => [],
            'dashboards' => [],
            'reports' => [],
            'workflows' => [],
            'notifications' => [],
            'documents' => [],
            'rules' => [],
            'integrations' => [],
        ];

        foreach ($this->resolvedModules() as $module) {
            $metadata = method_exists($module, 'runtimeMetadata')
                ? $module->runtimeMetadata()
                : [];

            foreach (array_keys($contributions) as $key) {
                if (isset($metadata[$key]) && is_array($metadata[$key])) {
                    $contributions[$key][] = $metadata[$key];
                }
            }
        }

        return $contributions;
    }

    /** @return list<object> */
    private function resolvedModules(): array
    {
        try {
            return [];
        } catch (\Throwable) {
            return [];
        }
    }
}
