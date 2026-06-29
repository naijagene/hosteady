<?php

namespace App\Services\Navigation;

use App\Modules\Sdk\Application\Data\NavigationGroup;
use App\Modules\Sdk\Application\Data\NavigationItem as ApplicationNavigationItem;
use App\Modules\Sdk\Application\Data\NavigationMenu;
use App\Modules\Sdk\Application\Data\NavigationRoute;
use App\Modules\Sdk\Navigation\Data\NavigationItem;
use App\Modules\Sdk\Navigation\Data\NavigationRenderPayload;
use App\Modules\Sdk\Navigation\Enums\NavigationItemType;
use App\Support\Tenant\TenantContext;

class NavigationApplicationRuntimeBridge
{
    public function __construct(
        private readonly NavigationRendererService $rendererService,
    ) {
    }

    /** @return list<NavigationMenu> */
    public function buildMenusForRuntime(TenantContext $context, ?string $navigationKey = 'main', ?string $moduleKey = null): array
    {
        try {
            if (! (bool) config('heos.enterprise.navigation_designer.enabled', true)) {
                return [];
            }

            $payload = $this->rendererService->render($context, $navigationKey ?? 'main', $moduleKey, false);
            $menu = $this->convertPayloadToMenu($payload, $navigationKey ?? 'main');

            return $menu !== null ? [$menu] : [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function convertPayloadToMenu(NavigationRenderPayload $payload, string $menuKey): ?NavigationMenu
    {
        if ($payload->items === []) {
            return null;
        }

        $groups = [];
        $rootItems = [];
        $itemsByKey = [];

        foreach ($payload->items as $itemData) {
            if (! is_array($itemData)) {
                continue;
            }

            $item = NavigationItem::fromArray($itemData);
            $itemsByKey[$item->publicId !== '' ? $item->publicId : $item->itemKey] = $item;
        }

        foreach ($itemsByKey as $item) {
            if ($item->parentItemPublicId !== null && $item->parentItemPublicId !== '') {
                continue;
            }

            if ($item->itemType === NavigationItemType::Group->value) {
                $groups[] = new NavigationGroup(
                    groupKey: $item->itemKey,
                    label: $item->label,
                    sortOrder: $item->sortOrder,
                    items: array_map(
                        fn (NavigationItem $child) => $this->toApplicationItem($child)->toArray(),
                        $this->childrenOf($item, $itemsByKey),
                    ),
                    metadata: $item->metadata,
                );
            } else {
                $rootItems[] = $this->toApplicationItem($item);
            }
        }

        if ($groups === [] && $rootItems !== []) {
            $groups[] = new NavigationGroup(
                groupKey: 'default',
                label: (string) ($payload->definition['name'] ?? 'Main'),
                sortOrder: 0,
                items: array_map(fn (ApplicationNavigationItem $item) => $item->toArray(), $rootItems),
                metadata: ['source' => 'navigation_designer'],
            );
        }

        if ($groups === []) {
            return null;
        }

        return new NavigationMenu(
            menuKey: $menuKey,
            label: (string) ($payload->definition['name'] ?? ucfirst($menuKey)),
            groups: array_map(fn (NavigationGroup $group) => $group->toArray(), $groups),
            metadata: [
                'source' => 'navigation_designer',
                'definition_public_id' => $payload->definition['public_id'] ?? null,
            ],
        );
    }

    /**
     * @param  array<string, NavigationItem>  $itemsByKey
     * @return list<NavigationItem>
     */
    private function childrenOf(NavigationItem $parent, array $itemsByKey): array
    {
        $children = [];

        foreach ($itemsByKey as $item) {
            if ($item->parentItemPublicId === $parent->publicId || $item->parentItemPublicId === $parent->itemKey) {
                $children[] = $item;
            }
        }

        usort($children, fn (NavigationItem $a, NavigationItem $b) => $a->sortOrder <=> $b->sortOrder);

        return $children;
    }

    private function toApplicationItem(NavigationItem $item): ApplicationNavigationItem
    {
        $route = [];

        if ($item->route !== null && $item->route !== '') {
            $route = (new NavigationRoute(
                name: $item->itemKey,
                path: $item->route,
                moduleKey: $item->moduleKey,
                parameters: [],
            ))->toArray();
        }

        $requiredPermission = $item->permissions[0] ?? null;

        return new ApplicationNavigationItem(
            itemKey: $item->itemKey,
            label: $item->label,
            itemType: $item->itemType,
            route: $route,
            badge: $item->badge,
            sortOrder: $item->sortOrder,
            requiredPermission: is_string($requiredPermission) ? $requiredPermission : null,
            metadata: array_merge($item->metadata, [
                'navigation_item_public_id' => $item->publicId,
                'icon' => $item->icon,
            ]),
        );
    }
}
