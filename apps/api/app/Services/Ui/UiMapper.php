<?php

namespace App\Services\Ui;

use App\Models\ApplicationRuntime\ApplicationRuntimeApp;
use App\Models\UiComponent;
use App\Models\UiLayout;
use App\Models\UiPage;
use App\Models\UiPersonalization;
use App\Modules\Sdk\Ui\Data\UiComponent as UiComponentDto;
use App\Modules\Sdk\Ui\Data\UiLayout as UiLayoutDto;
use App\Modules\Sdk\Ui\Data\UiPageDefinition;
use App\Modules\Sdk\Ui\Data\UiPersonalization as UiPersonalizationDto;
use Illuminate\Database\Eloquent\Builder;

class UiMapper
{
    public static function toPageDefinition(UiPage $model): UiPageDefinition
    {
        return new UiPageDefinition(
            publicId: $model->public_id,
            moduleKey: $model->module_key,
            pageKey: $model->page_key,
            name: $model->name,
            description: $model->description,
            pageType: $model->page_type,
            status: $model->status,
            visibility: $model->visibility,
            routePath: $model->route_path,
            icon: $model->icon,
            layout: is_array($model->layout_json) ? $model->layout_json : [],
            regions: is_array($model->regions_json) ? $model->regions_json : [],
            components: is_array($model->components_json) ? $model->components_json : [],
            actions: is_array($model->actions_json) ? $model->actions_json : [],
            conditions: is_array($model->conditions_json) ? $model->conditions_json : [],
            breakpoints: is_array($model->breakpoints_json) ? $model->breakpoints_json : [],
            theme: is_array($model->theme_json) ? $model->theme_json : [],
            metadata: is_array($model->metadata) ? $model->metadata : [],
            applicationPublicId: self::resolveApplicationPublicId($model->application_id),
        );
    }

    public static function toLayout(UiLayout $model): UiLayoutDto
    {
        return new UiLayoutDto(
            publicId: $model->public_id,
            layoutKey: $model->layout_key,
            name: $model->name,
            description: $model->description,
            layoutType: $model->layout_type,
            status: $model->status,
            regions: is_array($model->regions_json) ? $model->regions_json : [],
            breakpoints: is_array($model->breakpoints_json) ? $model->breakpoints_json : [],
            metadata: is_array($model->metadata) ? $model->metadata : [],
            moduleKey: $model->module_key,
        );
    }

    public static function toComponent(UiComponent $model): UiComponentDto
    {
        return new UiComponentDto(
            publicId: $model->public_id,
            componentKey: $model->component_key,
            name: $model->name,
            description: $model->description,
            componentType: $model->component_type,
            status: $model->status,
            bindingType: $model->binding_type,
            bindingConfig: is_array($model->binding_config) ? $model->binding_config : [],
            actions: is_array($model->actions_json) ? $model->actions_json : [],
            conditions: is_array($model->conditions_json) ? $model->conditions_json : [],
            metadata: is_array($model->metadata) ? $model->metadata : [],
            moduleKey: $model->module_key,
        );
    }

    public static function toPersonalization(UiPersonalization $model): UiPersonalizationDto
    {
        return new UiPersonalizationDto(
            publicId: $model->public_id,
            pagePublicId: $model->page_public_id,
            membershipPublicId: self::resolveMembershipPublicId($model->membership_id),
            personalization: is_array($model->personalization_json) ? $model->personalization_json : [],
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    /**
     * @param  Builder<UiPage|UiLayout|UiComponent|UiPersonalization>  $query
     */
    public static function applyOrganizationScope(Builder $query, string $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * @param  Builder<UiPage|UiLayout|UiComponent|UiPersonalization>  $query
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

    private static function resolveApplicationPublicId(?string $applicationId): ?string
    {
        if ($applicationId === null || $applicationId === '') {
            return null;
        }

        return ApplicationRuntimeApp::query()
            ->where('id', $applicationId)
            ->value('public_id');
    }

    private static function resolveMembershipPublicId(?string $membershipId): ?string
    {
        if ($membershipId === null || $membershipId === '') {
            return null;
        }

        return \App\Models\OrganizationMembership::query()
            ->where('id', $membershipId)
            ->value('public_id');
    }
}
