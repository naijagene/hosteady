<?php

namespace App\Services\Theme;

use App\Models\BrandProfile;
use App\Models\ThemeDefinition;
use App\Models\ThemeVersion;
use App\Modules\Sdk\Theme\Data\ThemeStatistics;
use App\Modules\Sdk\Theme\Enums\ThemeDefinitionStatus;
use App\Models\Organization;
use App\Models\Workspace;

class ThemeStatisticsService
{
    public function __construct(
        private readonly ThemeTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function statisticsForScope(Organization $organization, ?Workspace $workspace): ThemeStatistics
    {
        if (! $this->tableHealthSupport->coreTablesPresent()) {
            return $this->tableHealthSupport->emptyStatistics();
        }

        $definitions = ThemeDefinition::query();
        ThemeMapper::applyOrganizationScope($definitions, $organization->id);
        ThemeMapper::applyWorkspaceScope($definitions, $workspace?->id);

        $versions = ThemeVersion::query();
        ThemeMapper::applyOrganizationScope($versions, $organization->id);
        ThemeMapper::applyWorkspaceScope($versions, $workspace?->id);

        $brands = BrandProfile::query();
        ThemeMapper::applyOrganizationScope($brands, $organization->id);
        ThemeMapper::applyWorkspaceScope($brands, $workspace?->id);

        return new ThemeStatistics(
            definitions: $definitions->count(),
            versions: $versions->count(),
            brandProfiles: $brands->count(),
            publishedDefinitions: (clone $definitions)->where('status', ThemeDefinitionStatus::Published->value)->count(),
            registeredModules: (clone $definitions)->distinct('module_key')->count('module_key'),
        );
    }
}
