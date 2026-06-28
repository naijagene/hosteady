<?php

namespace App\Services\Application;

use App\Models\ApplicationRuntime\ApplicationNavigation;
use App\Models\ApplicationRuntime\ApplicationRuntimeApp;
use App\Modules\Sdk\Application\Contracts\ApplicationRuntime;
use App\Modules\Sdk\Application\Contracts\NavigationProvider;
use App\Modules\Sdk\Application\Data\ApplicationDefinition;
use App\Modules\Sdk\Application\Data\ApplicationRuntimeMetadata;
use App\Modules\Sdk\Application\Data\NavigationMenu;
use App\Modules\Sdk\Application\Enums\ApplicationStatus;
use App\Support\Tenant\TenantContext;

class ApplicationRuntimeService implements ApplicationRuntime, NavigationProvider
{
    public function __construct(
        private readonly ApplicationRuntimeRegistryService $registryService,
        private readonly NavigationBuilderService $navigationBuilder,
        private readonly ApplicationDiscoveryService $discoveryService,
        private readonly ApplicationLoaderService $loaderService,
    ) {
    }

    public function load(TenantContext $context): ApplicationRuntimeMetadata
    {
        $apps = $this->listApplications($context);
        $enabled = array_values(array_filter($apps, fn (ApplicationDefinition $app) => $app->status === ApplicationStatus::Enabled->value));
        $navigation = $this->navigation($context);
        $discovered = $this->discoveryService->discoverModuleContributions($context);

        return new ApplicationRuntimeMetadata(
            applicationKey: 'platform',
            enabled: (bool) config('heos.enterprise.application_runtime.enabled', true),
            capabilities: $this->loaderService->aggregateCapabilities($context, $enabled, $discovered),
            navigation: array_map(fn (NavigationMenu $menu) => $menu->toArray(), $navigation),
            menus: $this->navigationBuilder->buildMenus($context, $navigation),
            workspace: $this->loaderService->workspaceMetadata($context, $enabled),
            metadata: [
                'registered_apps' => count($apps),
                'enabled_apps' => count($enabled),
                'navigation_nodes' => $this->countNavigationNodes($navigation),
            ],
        );
    }

    /** @return list<ApplicationDefinition> */
    public function listApplications(TenantContext $context): array
    {
        return $this->registryService->list(
            $context->organization->id,
            $context->workspace?->id,
        );
    }

    /** @return list<NavigationMenu> */
    public function navigation(TenantContext $context): array
    {
        return $this->navigationBuilder->build($context);
    }

    /** @param  list<NavigationMenu>  $menus */
    private function countNavigationNodes(array $menus): int
    {
        $count = 0;

        foreach ($menus as $menu) {
            foreach ($menu->groups as $group) {
                $groupData = is_array($group) ? $group : $group;
                $items = is_array($groupData['items'] ?? null) ? $groupData['items'] : [];
                $count += count($items);
            }
        }

        return $count;
    }
}
