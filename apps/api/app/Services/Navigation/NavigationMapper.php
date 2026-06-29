<?php

namespace App\Services\Navigation;

use App\Models\ApplicationRuntime\ApplicationRuntimeApp;
use App\Models\NavigationDefinition;
use App\Models\NavigationItem;
use App\Models\NavigationPersonalization;
use App\Models\NavigationVersion;
use App\Models\OrganizationMembership;
use App\Modules\Sdk\Navigation\Data\NavigationDefinition as NavigationDefinitionDto;
use App\Modules\Sdk\Navigation\Data\NavigationItem as NavigationItemDto;
use App\Modules\Sdk\Navigation\Data\NavigationPersonalization as NavigationPersonalizationDto;
use App\Modules\Sdk\Navigation\Data\NavigationVersion as NavigationVersionDto;
use Illuminate\Database\Eloquent\Builder;

class NavigationMapper
{
    public static function toDefinition(NavigationDefinition $model): NavigationDefinitionDto
    {
        return new NavigationDefinitionDto(
            publicId: $model->public_id,
            moduleKey: $model->module_key,
            navigationKey: $model->navigation_key,
            name: $model->name,
            description: $model->description,
            type: $model->type,
            status: $model->status,
            visibility: $model->visibility,
            scope: $model->scope,
            structure: is_array($model->structure_json) ? $model->structure_json : [],
            conditions: is_array($model->conditions_json) ? $model->conditions_json : [],
            metadata: is_array($model->metadata) ? $model->metadata : [],
            applicationPublicId: self::resolveApplicationPublicId($model->application_id),
            currentVersionPublicId: self::resolveVersionPublicId($model->current_version_id),
        );
    }

    public static function toVersion(NavigationVersion $model): NavigationVersionDto
    {
        return new NavigationVersionDto(
            publicId: $model->public_id,
            navigationDefinitionPublicId: self::resolveDefinitionPublicId($model->navigation_definition_id) ?? '',
            versionNumber: (int) $model->version_number,
            status: $model->status,
            structure: is_array($model->structure_json) ? $model->structure_json : [],
            conditions: is_array($model->conditions_json) ? $model->conditions_json : [],
            changeSummary: $model->change_summary,
            metadata: is_array($model->metadata) ? $model->metadata : [],
            publishedAt: $model->published_at?->toIso8601String(),
        );
    }

    public static function toItem(NavigationItem $model): NavigationItemDto
    {
        return new NavigationItemDto(
            publicId: $model->public_id,
            navigationDefinitionPublicId: self::resolveDefinitionPublicId($model->navigation_definition_id),
            parentItemPublicId: self::resolveItemPublicId($model->parent_item_id),
            moduleKey: $model->module_key,
            itemKey: $model->item_key,
            label: $model->label,
            itemType: $model->item_type,
            route: $model->route,
            icon: $model->icon,
            badge: is_array($model->badge_json) ? $model->badge_json : [],
            visibility: $model->visibility,
            conditions: is_array($model->conditions_json) ? $model->conditions_json : [],
            permissions: is_array($model->permissions_json) ? $model->permissions_json : [],
            roles: is_array($model->roles_json) ? $model->roles_json : [],
            sortOrder: (int) $model->sort_order,
            metadata: is_array($model->metadata) ? $model->metadata : [],
            applicationPublicId: self::resolveApplicationPublicId($model->application_id),
        );
    }

    public static function toPersonalization(NavigationPersonalization $model): NavigationPersonalizationDto
    {
        return new NavigationPersonalizationDto(
            publicId: $model->public_id,
            navigationDefinitionPublicId: self::resolveDefinitionPublicId($model->navigation_definition_id),
            membershipPublicId: self::resolveMembershipPublicId($model->membership_id),
            personalization: is_array($model->personalization_json) ? $model->personalization_json : [],
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    /**
     * @param  Builder<NavigationDefinition|NavigationVersion|NavigationItem|NavigationPersonalization>  $query
     */
    public static function applyOrganizationScope(Builder $query, string $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * @param  Builder<NavigationDefinition|NavigationVersion|NavigationItem|NavigationPersonalization>  $query
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

    public static function resolveApplicationId(?string $applicationPublicId): ?string
    {
        if ($applicationPublicId === null || $applicationPublicId === '') {
            return null;
        }

        return ApplicationRuntimeApp::query()
            ->where('public_id', $applicationPublicId)
            ->value('id');
    }

    public static function resolveDefinitionId(?string $definitionPublicId): ?string
    {
        if ($definitionPublicId === null || $definitionPublicId === '') {
            return null;
        }

        return NavigationDefinition::query()
            ->where('public_id', $definitionPublicId)
            ->value('id');
    }

    public static function resolveItemId(?string $itemPublicId): ?string
    {
        if ($itemPublicId === null || $itemPublicId === '') {
            return null;
        }

        return NavigationItem::query()
            ->where('public_id', $itemPublicId)
            ->value('id');
    }

    private static function resolveApplicationPublicId(?string $applicationId): ?string
    {
        if ($applicationId === null || $applicationId === '') {
            return null;
        }

        return ApplicationRuntimeApp::query()
            ->where('id', $applicationId)
            ->value('public_id');
    }

    private static function resolveDefinitionPublicId(?string $definitionId): ?string
    {
        if ($definitionId === null || $definitionId === '') {
            return null;
        }

        return NavigationDefinition::query()
            ->where('id', $definitionId)
            ->value('public_id');
    }

    private static function resolveVersionPublicId(?string $versionId): ?string
    {
        if ($versionId === null || $versionId === '') {
            return null;
        }

        return NavigationVersion::query()
            ->where('id', $versionId)
            ->value('public_id');
    }

    private static function resolveItemPublicId(?string $itemId): ?string
    {
        if ($itemId === null || $itemId === '') {
            return null;
        }

        return NavigationItem::query()
            ->where('id', $itemId)
            ->value('public_id');
    }

    private static function resolveMembershipPublicId(?string $membershipId): ?string
    {
        if ($membershipId === null || $membershipId === '') {
            return null;
        }

        return OrganizationMembership::query()
            ->where('id', $membershipId)
            ->value('public_id');
    }
}
