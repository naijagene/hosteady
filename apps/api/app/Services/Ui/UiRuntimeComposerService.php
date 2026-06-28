<?php

namespace App\Services\Ui;

use App\Modules\Sdk\Ui\Contracts\UiRuntimeComposer;
use App\Modules\Sdk\Ui\Data\UiPageReference;
use App\Modules\Sdk\Ui\Data\UiRenderPayload;
use App\Support\Tenant\TenantContext;

class UiRuntimeComposerService implements UiRuntimeComposer
{
    public function __construct(
        private readonly UiPageRegistryService $pageRegistry,
        private readonly UiLayoutService $layoutService,
        private readonly UiComponentService $componentService,
        private readonly UiPermissionBridge $permissionBridge,
        private readonly UiStatisticsService $statisticsService,
        private readonly UiFormBridge $formBridge,
        private readonly UiTableBridge $tableBridge,
        private readonly UiDashboardBridge $dashboardBridge,
        private readonly UiReportBridge $reportBridge,
        private readonly UiWorkflowBridge $workflowBridge,
        private readonly UiTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function compose(TenantContext $context): UiRenderPayload
    {
        $permissions = $this->permissionBridge->renderPermissions($context);

        if (! $this->tableHealthSupport->coreTablesPresent()) {
            return $this->tableHealthSupport->emptyRenderPayload($context, $permissions);
        }

        $pages = $this->pageRegistry->list($context->organization->id, $context->workspace?->id, 100);
        $layouts = $this->layoutService->list($context->organization->id, $context->workspace?->id, 100);
        $components = $this->componentService->list($context->organization->id, $context->workspace?->id, 100);
        $stats = $this->statisticsService->statisticsForScope($context->organization, $context->workspace);

        $pageReferences = array_map(
            fn ($page) => UiPageReference::fromArray([
                'public_id' => $page->publicId,
                'module_key' => $page->moduleKey,
                'page_key' => $page->pageKey,
                'name' => $page->name,
                'page_type' => $page->pageType,
                'status' => $page->status,
                'route_path' => $page->routePath,
            ])->toArray(),
            $pages,
        );

        $bindings = $this->composeBindings($components);

        return new UiRenderPayload(
            page: [
                'runtime' => true,
                'page_count' => count($pages),
            ],
            layout: [
                'registered_count' => count($layouts),
                'items' => array_map(fn ($layout) => $layout->toArray(), $layouts),
            ],
            regions: [],
            components: array_map(fn ($component) => $component->toArray(), $components),
            actions: [],
            conditions: [],
            breakpoints: [],
            theme: ['theme_key' => 'runtime', 'name' => 'Runtime', 'tokens' => [], 'metadata' => []],
            personalization: [],
            permissions: $this->permissionBridge->renderPermissions($context),
            runtimeContext: [
                'enabled' => (bool) config('heos.enterprise.ui_metadata.enabled', true),
                'organization_public_id' => $context->organizationPublicId,
                'workspace_public_id' => $context->workspacePublicId,
                'pages' => $pageReferences,
                'bindings' => $bindings,
                'statistics' => $stats->toArray(),
            ],
        );
    }

    /** @return array<string, mixed> */
    public function runtimeMetadata(TenantContext $context): array
    {
        $stats = $this->statisticsService->statisticsForScope($context->organization, $context->workspace);

        return [
            'enabled' => (bool) config('heos.enterprise.ui_metadata.enabled', true),
            'pages' => $stats->pages,
            'layouts' => $stats->layouts,
            'components' => $stats->components,
            'personalizations' => $stats->personalizations,
            'registered_modules' => $stats->registeredModules,
        ];
    }

    /**
     * @param  list<\App\Modules\Sdk\Ui\Data\UiComponent>  $components
     * @return list<array<string, mixed>>
     */
    private function composeBindings(array $components): array
    {
        $bindings = [];

        foreach ($components as $component) {
            if ($component->bindingType === null || $component->bindingType === '') {
                continue;
            }

            $resourceKey = (string) ($component->bindingConfig['resource_key'] ?? '');
            $resolved = match ($component->bindingType) {
                'form' => $this->formBridge->resolveReferenceBestEffort($component->moduleKey, $resourceKey, $component->bindingConfig),
                'table' => $this->tableBridge->resolveReferenceBestEffort($component->moduleKey, $resourceKey, $component->bindingConfig),
                'dashboard' => $this->dashboardBridge->resolveReferenceBestEffort($component->moduleKey, $resourceKey, $component->bindingConfig),
                'report' => $this->reportBridge->resolveReferenceBestEffort($component->moduleKey, $resourceKey, $component->bindingConfig),
                'workflow' => $this->workflowBridge->resolveReferenceBestEffort($component->moduleKey, $resourceKey, $component->bindingConfig),
                default => null,
            };

            $bindings[] = [
                'component_key' => $component->componentKey,
                'binding_type' => $component->bindingType,
                'resolved' => $resolved !== null,
                'reference' => $resolved,
            ];
        }

        return $bindings;
    }
}
