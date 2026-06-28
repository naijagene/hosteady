<?php

namespace App\Services\Application;

use App\Models\ApplicationRuntime\ApplicationNavigation as ApplicationNavigationModel;
use App\Models\ApplicationRuntime\ApplicationRuntimeApp;
use App\Models\ApplicationRuntime\ApplicationWorkspace as ApplicationWorkspaceModel;
use App\Modules\Sdk\Application\Data\ApplicationDefinition;
use App\Modules\Sdk\Application\Data\ApplicationWorkspace;
use App\Modules\Sdk\Application\Data\NavigationGroup;
use App\Modules\Sdk\Application\Data\NavigationItem;
use App\Modules\Sdk\Application\Data\NavigationMenu;
use App\Modules\Sdk\Application\Data\NavigationRoute;
use Illuminate\Database\Eloquent\Builder;

class ApplicationRuntimeMapper
{
    public static function toDefinition(ApplicationRuntimeApp $model): ApplicationDefinition
    {
        return new ApplicationDefinition(
            publicId: $model->public_id,
            applicationKey: $model->application_key,
            name: $model->name,
            description: $model->description,
            applicationType: $model->application_type,
            status: $model->status,
            visibility: $model->visibility,
            moduleKey: $model->module_key,
            catalogApplicationPublicId: null,
            manifest: is_array($model->manifest_json) ? $model->manifest_json : [],
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toWorkspace(ApplicationWorkspaceModel $model): ApplicationWorkspace
    {
        return new ApplicationWorkspace(
            publicId: $model->public_id,
            workspaceKey: $model->workspace_key,
            name: $model->name,
            status: $model->status,
            applicationPublicId: $model->application?->public_id ?? '',
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toNavigationItem(ApplicationNavigationModel $model): NavigationItem
    {
        return new NavigationItem(
            itemKey: $model->navigation_key,
            label: $model->label,
            itemType: $model->item_type,
            route: is_array($model->route_json) ? $model->route_json : [],
            badge: is_array($model->badge_json) ? $model->badge_json : [],
            sortOrder: (int) $model->sort_order,
            requiredPermission: $model->required_permission,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    /** @param  list<ApplicationNavigationModel>  $models */
    public static function toNavigationMenu(string $menuKey, array $models): NavigationMenu
    {
        $groups = [];
        $itemsByParent = [];

        foreach ($models as $model) {
            $parent = $model->parent_key ?? '_root';
            $itemsByParent[$parent] ??= [];
            $itemsByParent[$parent][] = self::toNavigationItem($model);
        }

        foreach ($itemsByParent['_root'] ?? [] as $item) {
            if ($item->itemType === 'group') {
                $groups[] = new NavigationGroup(
                    groupKey: $item->itemKey,
                    label: $item->label,
                    sortOrder: $item->sortOrder,
                    items: $itemsByParent[$item->itemKey] ?? [],
                    metadata: $item->metadata,
                );
            }
        }

        if ($groups === [] && isset($itemsByParent['_root'])) {
            $groups[] = new NavigationGroup(
                groupKey: 'default',
                label: 'Main',
                sortOrder: 0,
                items: $itemsByParent['_root'],
                metadata: [],
            );
        }

        return new NavigationMenu(
            menuKey: $menuKey,
            label: ucfirst($menuKey),
            groups: array_map(fn (NavigationGroup $group) => $group->toArray(), $groups),
            metadata: [],
        );
    }

    /**
     * @param  Builder<ApplicationRuntimeApp|ApplicationNavigationModel|ApplicationWorkspaceModel>  $query
     */
    public static function applyWorkspaceScope(Builder $query, ?string $workspaceId): Builder
    {
        if ($workspaceId === null) {
            return $query;
        }

        return $query->where(function (Builder $scoped) use ($workspaceId) {
            $scoped->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
        });
    }

    public static function applyOrganizationScope(Builder $query, string $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }
}
