<?php

namespace App\Services\Application;

use App\Modules\Sdk\Application\Data\NavigationMenu;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class ApplicationPermissionBridge
{
    public function __construct(
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    public function canRead(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'application.read');
    }

    public function canManage(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'application.manage');
    }

    public function canReadNavigation(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'navigation.read');
    }

    public function canReadWorkspace(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'workspace.read')
            || $this->authorizationService->allows($context, 'application.read');
    }

    /**
     * @param  list<NavigationMenu>  $menus
     * @return list<NavigationMenu>
     */
    public function filterMenus(TenantContext $context, array $menus): array
    {
        $filtered = [];

        foreach ($menus as $menu) {
            $groups = [];

            foreach ($menu->groups as $group) {
                $groupData = is_array($group) ? $group : $group;
                $items = is_array($groupData['items'] ?? null) ? $groupData['items'] : [];
                $visibleItems = array_values(array_filter($items, function (array $item) use ($context) {
                    $permission = $item['required_permission'] ?? null;

                    return $permission === null
                        || $permission === ''
                        || $this->authorizationService->allows($context, (string) $permission);
                }));

                if ($visibleItems !== []) {
                    $groupData['items'] = $visibleItems;
                    $groups[] = $groupData;
                }
            }

            if ($groups !== []) {
                $filtered[] = NavigationMenu::fromArray(array_merge($menu->toArray(), ['groups' => $groups]));
            }
        }

        return $filtered;
    }
}
