<?php

namespace App\Services\Theme;

use App\Models\ApplicationRuntime\ApplicationRuntimeApp;
use App\Models\BrandProfile;
use App\Models\OrganizationMembership;
use App\Models\ThemeDefinition;
use App\Models\ThemeVersion;
use App\Modules\Sdk\Theme\Data\BrandProfile as BrandProfileDto;
use App\Modules\Sdk\Theme\Data\ThemeDefinition as ThemeDefinitionDto;
use App\Modules\Sdk\Theme\Data\ThemeVersion as ThemeVersionDto;
use Illuminate\Database\Eloquent\Builder;

class ThemeMapper
{
    public static function toDefinition(ThemeDefinition $model): ThemeDefinitionDto
    {
        return new ThemeDefinitionDto(
            publicId: $model->public_id,
            moduleKey: $model->module_key,
            themeKey: $model->theme_key,
            name: $model->name,
            description: $model->description,
            status: $model->status,
            scope: $model->scope,
            inheritanceMode: $model->inheritance_mode,
            parentThemePublicId: self::resolveThemePublicId($model->parent_theme_id),
            tokens: is_array($model->tokens_json) ? $model->tokens_json : [],
            metadata: is_array($model->metadata) ? $model->metadata : [],
            applicationPublicId: self::resolveApplicationPublicId($model->application_id),
            currentVersionPublicId: self::resolveVersionPublicId($model->current_version_id),
        );
    }

    public static function toBrandProfile(BrandProfile $model): BrandProfileDto
    {
        return new BrandProfileDto(
            publicId: $model->public_id,
            themeDefinitionPublicId: self::resolveThemePublicId($model->theme_definition_id),
            name: $model->name,
            logoUrl: $model->logo_url,
            colors: is_array($model->colors_json) ? $model->colors_json : [],
            typography: is_array($model->typography_json) ? $model->typography_json : [],
            assets: is_array($model->assets_json) ? $model->assets_json : [],
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toVersion(ThemeVersion $model): ThemeVersionDto
    {
        return new ThemeVersionDto(
            publicId: $model->public_id,
            themeDefinitionPublicId: self::resolveThemePublicId($model->theme_definition_id) ?? '',
            versionNumber: (int) $model->version_number,
            status: $model->status,
            snapshot: is_array($model->snapshot_json) ? $model->snapshot_json : [],
            changeSummary: $model->change_summary,
            metadata: is_array($model->metadata) ? $model->metadata : [],
            publishedAt: $model->published_at?->toIso8601String(),
        );
    }

    /**
     * @param  Builder<ThemeDefinition|ThemeVersion|BrandProfile>  $query
     */
    public static function applyOrganizationScope(Builder $query, string $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * @param  Builder<ThemeDefinition|ThemeVersion|BrandProfile>  $query
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

        return ApplicationRuntimeApp::query()->where('public_id', $applicationPublicId)->value('id');
    }

    public static function resolveThemeId(?string $themePublicId): ?string
    {
        if ($themePublicId === null || $themePublicId === '') {
            return null;
        }

        return ThemeDefinition::query()->where('public_id', $themePublicId)->value('id');
    }

    public static function resolveVersionId(?string $versionPublicId): ?string
    {
        if ($versionPublicId === null || $versionPublicId === '') {
            return null;
        }

        return ThemeVersion::query()->where('public_id', $versionPublicId)->value('id');
    }

    public static function resolveMembershipPublicId(?string $membershipId): ?string
    {
        if ($membershipId === null || $membershipId === '') {
            return null;
        }

        return OrganizationMembership::query()->where('id', $membershipId)->value('public_id');
    }

    private static function resolveApplicationPublicId(?string $applicationId): ?string
    {
        if ($applicationId === null || $applicationId === '') {
            return null;
        }

        return ApplicationRuntimeApp::query()->where('id', $applicationId)->value('public_id');
    }

    private static function resolveThemePublicId(?string $themeId): ?string
    {
        if ($themeId === null || $themeId === '') {
            return null;
        }

        return ThemeDefinition::query()->where('id', $themeId)->value('public_id');
    }

    private static function resolveVersionPublicId(?string $versionId): ?string
    {
        if ($versionId === null || $versionId === '') {
            return null;
        }

        return ThemeVersion::query()->where('id', $versionId)->value('public_id');
    }
}
