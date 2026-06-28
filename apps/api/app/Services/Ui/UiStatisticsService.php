<?php

namespace App\Services\Ui;

use App\Models\Organization;
use App\Models\UiComponent;
use App\Models\UiLayout;
use App\Models\UiPage;
use App\Models\UiPersonalization;
use App\Models\Workspace;
use App\Modules\Sdk\Ui\Data\UiStatistics;

class UiStatisticsService
{
    public function __construct(
        private readonly UiTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function statisticsForScope(?Organization $organization, ?Workspace $workspace): UiStatistics
    {
        if ($organization === null || ! $this->tableHealthSupport->coreTablesPresent()) {
            return $this->tableHealthSupport->emptyStatistics();
        }

        $pages = 0;
        $registeredModules = 0;

        if ($this->tableHealthSupport->isTablePresent('ui_pages')) {
            $pagesQuery = UiPage::query();
            UiMapper::applyOrganizationScope($pagesQuery, $organization->id);
            UiMapper::applyWorkspaceScope($pagesQuery, $workspace?->id);
            $pages = (clone $pagesQuery)->count();
            $registeredModules = (clone $pagesQuery)
                ->whereNotNull('module_key')
                ->distinct('module_key')
                ->count('module_key');
        }

        $layouts = 0;

        if ($this->tableHealthSupport->isTablePresent('ui_layouts')) {
            $layoutsQuery = UiLayout::query();
            UiMapper::applyOrganizationScope($layoutsQuery, $organization->id);
            UiMapper::applyWorkspaceScope($layoutsQuery, $workspace?->id);
            $layouts = $layoutsQuery->count();
        }

        $components = 0;

        if ($this->tableHealthSupport->isTablePresent('ui_components')) {
            $componentsQuery = UiComponent::query();
            UiMapper::applyOrganizationScope($componentsQuery, $organization->id);
            UiMapper::applyWorkspaceScope($componentsQuery, $workspace?->id);
            $components = $componentsQuery->count();
        }

        $personalizations = 0;

        if ($this->tableHealthSupport->isTablePresent('ui_personalizations')) {
            $personalizationsQuery = UiPersonalization::query();
            UiMapper::applyOrganizationScope($personalizationsQuery, $organization->id);
            UiMapper::applyWorkspaceScope($personalizationsQuery, $workspace?->id);
            $personalizations = $personalizationsQuery->count();
        }

        return new UiStatistics(
            pages: $pages,
            layouts: $layouts,
            components: $components,
            personalizations: $personalizations,
            registeredModules: $registeredModules,
        );
    }
}
