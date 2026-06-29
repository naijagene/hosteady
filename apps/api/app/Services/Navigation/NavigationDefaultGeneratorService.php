<?php

namespace App\Services\Navigation;

use App\Models\ApplicationRuntime\ApplicationRuntimeApp;
use App\Models\UiPage;
use App\Modules\Sdk\Navigation\Data\NavigationDefinition;
use App\Modules\Sdk\Navigation\Data\NavigationItem;
use App\Modules\Sdk\Navigation\Enums\NavigationDefinitionStatus;
use App\Modules\Sdk\Navigation\Enums\NavigationItemType;
use App\Modules\Sdk\Navigation\Enums\NavigationScope;
use App\Modules\Sdk\Navigation\Enums\NavigationType;
use App\Modules\Sdk\Navigation\Enums\NavigationVisibility;
use App\Services\Application\ApplicationRuntimeRegistryService;
use App\Services\Dashboard\DynamicDashboardRegistryService;
use App\Services\Form\DynamicFormRegistryService;
use App\Services\Report\DynamicReportRegistryService;
use App\Services\Table\DynamicTableRegistryService;
use App\Services\Ui\UiPageRegistryService;
use App\Support\Tenant\TenantContext;

class NavigationDefaultGeneratorService
{
    public function __construct(
        private readonly ApplicationRuntimeRegistryService $applicationRegistry,
        private readonly UiPageRegistryService $uiPageRegistry,
        private readonly DynamicFormRegistryService $formRegistry,
        private readonly DynamicTableRegistryService $tableRegistry,
        private readonly DynamicDashboardRegistryService $dashboardRegistry,
        private readonly DynamicReportRegistryService $reportRegistry,
        private readonly NavigationTableHealthSupport $tableHealthSupport,
    ) {
    }

    /**
     * @return array{definition: NavigationDefinition, items: list<NavigationItem>}
     */
    public function generateBestEffort(TenantContext $context, string $navigationKey, ?string $moduleKey = null): array
    {
        $items = [];
        $sortOrder = 0;

        $items = array_merge($items, $this->itemsFromApplications($context, $sortOrder));
        $sortOrder = count($items);

        if ($moduleKey !== null && $moduleKey !== '') {
            $items = array_merge($items, $this->itemsFromModuleMetadata($context, $moduleKey, $sortOrder));
        } else {
            $items = array_merge($items, $this->itemsFromAllModuleMetadata($context, $sortOrder));
        }

        $definition = new NavigationDefinition(
            publicId: '',
            moduleKey: $moduleKey,
            navigationKey: $navigationKey,
            name: ucfirst(str_replace(['_', '-'], ' ', $navigationKey)),
            description: 'Auto-generated navigation definition',
            type: NavigationType::Primary->value,
            status: NavigationDefinitionStatus::Draft->value,
            visibility: NavigationVisibility::Authenticated->value,
            scope: NavigationScope::Workspace->value,
            structure: ['generated' => true, 'item_count' => count($items)],
            conditions: [],
            metadata: ['source' => 'default_generator', 'best_effort' => true],
            applicationPublicId: null,
            currentVersionPublicId: null,
        );

        return ['definition' => $definition, 'items' => $items];
    }

    /** @return list<NavigationItem> */
    private function itemsFromApplications(TenantContext $context, int $startOrder): array
    {
        $items = [];
        $sortOrder = $startOrder;

        try {
            $applications = ApplicationRuntimeApp::query()
                ->orderBy('name')
                ->limit(25);

            NavigationMapper::applyOrganizationScope($applications, $context->organization->id);
            NavigationMapper::applyWorkspaceScope($applications, $context->workspace?->id);

            foreach ($applications->get() as $application) {
                $items[] = new NavigationItem(
                    publicId: '',
                    navigationDefinitionPublicId: null,
                    parentItemPublicId: null,
                    moduleKey: $application->module_key,
                    itemKey: 'app_'.$application->application_key,
                    label: $application->name,
                    itemType: NavigationItemType::Group->value,
                    route: null,
                    icon: null,
                    badge: [],
                    visibility: NavigationVisibility::Authenticated->value,
                    conditions: [],
                    permissions: ['application.read'],
                    roles: [],
                    sortOrder: $sortOrder++,
                    metadata: ['application_public_id' => $application->public_id, 'generated' => true],
                    applicationPublicId: $application->public_id,
                );
            }
        } catch (\Throwable) {
        }

        return $items;
    }

    /** @return list<NavigationItem> */
    private function itemsFromAllModuleMetadata(TenantContext $context, int $startOrder): array
    {
        $items = [];
        $sortOrder = $startOrder;

        try {
            foreach ($this->applicationRegistry->list($context->organization->id, $context->workspace?->id) as $application) {
                if ($application->moduleKey === null || $application->moduleKey === '') {
                    continue;
                }

                $moduleItems = $this->itemsFromModuleMetadata($context, $application->moduleKey, $sortOrder);
                $items = array_merge($items, $moduleItems);
                $sortOrder += count($moduleItems);
            }
        } catch (\Throwable) {
        }

        return $items;
    }

    /** @return list<NavigationItem> */
    private function itemsFromModuleMetadata(TenantContext $context, string $moduleKey, int $startOrder): array
    {
        $items = [];
        $sortOrder = $startOrder;
        $organizationId = $context->organization->id;
        $workspaceId = $context->workspace?->id;

        try {
            foreach ($this->uiPageRegistry->list($organizationId, $workspaceId) as $page) {
                if ($page->moduleKey !== $moduleKey) {
                    continue;
                }

                $items[] = $this->makeLinkItem($moduleKey, 'page_'.$page->pageKey, $page->name, $page->routePath, $sortOrder++, [
                    'page_public_id' => $page->publicId,
                    'resource_type' => 'ui_page',
                ]);
            }
        } catch (\Throwable) {
        }

        try {
            foreach ($this->formRegistry->list($moduleKey) as $form) {
                $items[] = $this->makeLinkItem($moduleKey, 'form_'.$form->formKey, $form->name, null, $sortOrder++, [
                    'resource_type' => 'form',
                    'form_key' => $form->formKey,
                ]);
            }
        } catch (\Throwable) {
        }

        try {
            foreach ($this->tableRegistry->list($moduleKey) as $table) {
                $items[] = $this->makeLinkItem($moduleKey, 'table_'.$table->tableKey, $table->name, null, $sortOrder++, [
                    'resource_type' => 'table',
                    'table_key' => $table->tableKey,
                ]);
            }
        } catch (\Throwable) {
        }

        try {
            foreach ($this->dashboardRegistry->list($moduleKey) as $dashboard) {
                $items[] = $this->makeLinkItem($moduleKey, 'dashboard_'.$dashboard->dashboardKey, $dashboard->name, null, $sortOrder++, [
                    'resource_type' => 'dashboard',
                    'dashboard_key' => $dashboard->dashboardKey,
                ]);
            }
        } catch (\Throwable) {
        }

        try {
            foreach ($this->reportRegistry->list($moduleKey) as $report) {
                $items[] = $this->makeLinkItem($moduleKey, 'report_'.$report->reportKey, $report->name, null, $sortOrder++, [
                    'resource_type' => 'report',
                    'report_key' => $report->reportKey,
                ]);
            }
        } catch (\Throwable) {
        }

        if ($this->tableHealthSupport->isTablePresent('ui_pages')) {
            try {
                $query = UiPage::query()->where('module_key', $moduleKey)->orderBy('name')->limit(50);
                NavigationMapper::applyOrganizationScope($query, $organizationId);
                NavigationMapper::applyWorkspaceScope($query, $workspaceId);

                foreach ($query->get() as $page) {
                    if ($this->containsItemKey($items, 'page_'.$page->page_key)) {
                        continue;
                    }

                    $items[] = $this->makeLinkItem($moduleKey, 'page_'.$page->page_key, $page->name, $page->route_path, $sortOrder++, [
                        'page_public_id' => $page->public_id,
                        'resource_type' => 'ui_page',
                    ]);
                }
            } catch (\Throwable) {
            }
        }

        return $items;
    }

    /**
     * @param  list<NavigationItem>  $items
     */
    private function containsItemKey(array $items, string $itemKey): bool
    {
        foreach ($items as $item) {
            if ($item->itemKey === $itemKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function makeLinkItem(
        string $moduleKey,
        string $itemKey,
        string $label,
        ?string $route,
        int $sortOrder,
        array $metadata,
    ): NavigationItem {
        return new NavigationItem(
            publicId: '',
            navigationDefinitionPublicId: null,
            parentItemPublicId: null,
            moduleKey: $moduleKey,
            itemKey: $itemKey,
            label: $label,
            itemType: NavigationItemType::Link->value,
            route: $route,
            icon: null,
            badge: [],
            visibility: NavigationVisibility::Authenticated->value,
            conditions: [],
            permissions: [],
            roles: [],
            sortOrder: $sortOrder,
            metadata: array_merge($metadata, ['generated' => true]),
            applicationPublicId: null,
        );
    }
}
