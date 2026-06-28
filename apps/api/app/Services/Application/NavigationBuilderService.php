<?php

namespace App\Services\Application;

use App\Models\ApplicationRuntime\ApplicationNavigation;
use App\Models\ApplicationRuntime\ApplicationRuntimeApp;
use App\Modules\Sdk\Application\Data\NavigationMenu;
use App\Modules\Sdk\Application\Enums\ApplicationStatus;
use App\Support\Tenant\TenantContext;

class NavigationBuilderService
{
    public function __construct(
        private readonly ApplicationPermissionBridge $permissionBridge,
        private readonly ApplicationDiscoveryService $discoveryService,
    ) {
    }

    /** @return list<NavigationMenu> */
    public function build(TenantContext $context): array
    {
        $menus = [];
        $query = ApplicationNavigation::query()->orderBy('sort_order');
        ApplicationRuntimeMapper::applyOrganizationScope($query, $context->organization->id);
        ApplicationRuntimeMapper::applyWorkspaceScope($query, $context->workspace?->id);
        $stored = $query->get()->groupBy('menu_key');

        foreach ($stored as $menuKey => $items) {
            $menus[] = ApplicationRuntimeMapper::toNavigationMenu((string) $menuKey, $items->all());
        }

        foreach ($this->discoveryService->discoverNavigation($context) as $menu) {
            $menus[] = $menu;
        }

        return $this->permissionBridge->filterMenus($context, $menus);
    }

    /**
     * @param  list<NavigationMenu>  $navigation
     * @return list<array<string, mixed>>
     */
    public function buildMenus(TenantContext $context, array $navigation): array
    {
        return array_map(fn (NavigationMenu $menu) => $menu->toArray(), $navigation);
    }

    public function syncFromModule(
        TenantContext $context,
        ApplicationRuntimeApp $application,
        array $navigationItems,
    ): void {
        foreach ($navigationItems as $item) {
            if (! is_array($item)) {
                continue;
            }

            ApplicationNavigation::query()->create([
                'id' => (string) \Illuminate\Support\Str::uuid7(),
                'organization_id' => $context->organization->id,
                'workspace_id' => $context->workspace?->id,
                'application_runtime_app_id' => $application->id,
                'menu_key' => (string) ($item['menu_key'] ?? 'main'),
                'navigation_key' => (string) ($item['item_key'] ?? $item['key'] ?? uniqid('nav_', true)),
                'label' => (string) ($item['label'] ?? 'Item'),
                'item_type' => (string) ($item['item_type'] ?? 'item'),
                'parent_key' => isset($item['parent_key']) ? (string) $item['parent_key'] : null,
                'sort_order' => (int) ($item['sort_order'] ?? 0),
                'route_json' => is_array($item['route'] ?? null) ? $item['route'] : [],
                'badge_json' => is_array($item['badge'] ?? null) ? $item['badge'] : [],
                'required_permission' => isset($item['required_permission']) ? (string) $item['required_permission'] : null,
                'metadata' => is_array($item['metadata'] ?? null) ? $item['metadata'] : [],
            ]);
        }
    }
}
