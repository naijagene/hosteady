<?php

namespace App\Services\Navigation;

use App\Models\NavigationDefinition;
use App\Models\NavigationItem;
use App\Models\NavigationPersonalization;
use App\Models\NavigationVersion;
use App\Models\Organization;
use App\Models\Workspace;
use App\Modules\Sdk\Navigation\Data\NavigationStatistics;

class NavigationStatisticsService
{
    public function __construct(
        private readonly NavigationTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function statisticsForScope(?Organization $organization, ?Workspace $workspace): NavigationStatistics
    {
        if ($organization === null || ! $this->tableHealthSupport->coreTablesPresent()) {
            return $this->tableHealthSupport->emptyStatistics();
        }

        $definitions = 0;
        $registeredModules = 0;

        if ($this->tableHealthSupport->isTablePresent('navigation_definitions')) {
            $definitionsQuery = NavigationDefinition::query();
            NavigationMapper::applyOrganizationScope($definitionsQuery, $organization->id);
            NavigationMapper::applyWorkspaceScope($definitionsQuery, $workspace?->id);
            $definitions = (clone $definitionsQuery)->count();
            $registeredModules = (clone $definitionsQuery)
                ->whereNotNull('module_key')
                ->distinct('module_key')
                ->count('module_key');
        }

        $versions = 0;

        if ($this->tableHealthSupport->isTablePresent('navigation_versions')) {
            $versionsQuery = NavigationVersion::query();
            NavigationMapper::applyOrganizationScope($versionsQuery, $organization->id);
            NavigationMapper::applyWorkspaceScope($versionsQuery, $workspace?->id);
            $versions = $versionsQuery->count();
        }

        $items = 0;

        if ($this->tableHealthSupport->isTablePresent('navigation_items')) {
            $itemsQuery = NavigationItem::query();
            NavigationMapper::applyOrganizationScope($itemsQuery, $organization->id);
            NavigationMapper::applyWorkspaceScope($itemsQuery, $workspace?->id);
            $items = $itemsQuery->count();
        }

        $personalizations = 0;

        if ($this->tableHealthSupport->isTablePresent('navigation_personalizations')) {
            $personalizationsQuery = NavigationPersonalization::query();
            NavigationMapper::applyOrganizationScope($personalizationsQuery, $organization->id);
            NavigationMapper::applyWorkspaceScope($personalizationsQuery, $workspace?->id);
            $personalizations = $personalizationsQuery->count();
        }

        return new NavigationStatistics(
            definitions: $definitions,
            versions: $versions,
            items: $items,
            personalizations: $personalizations,
            registeredModules: $registeredModules,
        );
    }
}
