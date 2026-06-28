<?php

namespace App\Services\Dashboard;

use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;
use App\Modules\Sdk\Dashboard\Data\DashboardHealthReport;
use App\Modules\Sdk\Dashboard\Data\DashboardStatistics;
use App\Modules\Sdk\Dashboard\Data\DashboardView;
use App\Modules\Sdk\Dashboard\Data\DashboardWidget;
use App\Modules\Sdk\Dashboard\Exceptions\DashboardNotFoundException;
use App\Services\Authorization\TenantAuthorizationService;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DynamicDashboardDevelopmentService
{
    public function __construct(
        private readonly DynamicDashboardRegistryService $registryService,
        private readonly DynamicDashboardDefinitionService $definitionService,
        private readonly DynamicDashboardGeneratorService $generatorService,
        private readonly DynamicDashboardRendererService $rendererService,
        private readonly DynamicDashboardWidgetService $widgetService,
        private readonly DynamicDashboardViewService $viewService,
        private readonly DynamicDashboardActivityService $activityService,
        private readonly DynamicDashboardHealthService $healthService,
        private readonly DynamicDashboardStatisticsService $statisticsService,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    /**
     * @return list<DashboardDefinition>
     */
    public function listDefinitions(TenantContext $context, ?string $moduleKey = null): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->registryService->list($moduleKey);
    }

    public function showDefinition(TenantContext $context, string $moduleKey, string $dashboardKey): DashboardDefinition
    {
        return $this->findDefinition($context, $moduleKey, $dashboardKey);
    }

    public function findDefinition(TenantContext $context, string $moduleKey, string $dashboardKey): DashboardDefinition
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $definition = $this->registryService->find($moduleKey, $dashboardKey);

        if ($definition === null) {
            throw new DashboardNotFoundException(sprintf('Dashboard [%s.%s] was not found.', $moduleKey, $dashboardKey));
        }

        return $definition;
    }

    /**
     * @param  DashboardDefinition|array<string, mixed>  $source
     */
    public function registerDefinition(TenantContext $context, mixed $source): DashboardDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->registryService->register($source);
    }

    public function generateEntityDashboard(TenantContext $context, string $moduleKey, string $entityKey): DashboardDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->generatorService->generateEntityDashboard($moduleKey, $entityKey);
    }

    /**
     * @return array<string, mixed>
     */
    public function renderDashboard(TenantContext $context, DashboardDefinition $definition, array $renderContext = []): array
    {
        $this->requireCapability($context);
        $this->assertRender($context);

        return $this->rendererService->render($definition, $renderContext);
    }

    /**
     * @return list<DashboardWidget>
     */
    public function listWidgets(TenantContext $context, string $moduleKey, string $dashboardKey): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $definition = $this->findDefinition($context, $moduleKey, $dashboardKey);

        return $this->widgetService->listWidgets($definition);
    }

    public function createWidget(TenantContext $context, string $moduleKey, string $dashboardKey, DashboardWidget $widget): DashboardWidget
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        $definition = $this->findDefinition($context, $moduleKey, $dashboardKey);

        return $this->widgetService->createWidget($definition, $widget);
    }

    public function updateWidget(TenantContext $context, DashboardWidget $widget): DashboardWidget
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->widgetService->updateWidget($widget);
    }

    public function deleteWidgetByPublicId(TenantContext $context, string $widgetPublicId): void
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        $this->widgetService->deleteWidget($widgetPublicId);
    }

    /**
     * @return list<DashboardView>
     */
    public function listViews(TenantContext $context, string $moduleKey, string $dashboardKey): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $this->findDefinition($context, $moduleKey, $dashboardKey);

        return $this->viewService->listViews(
            $moduleKey,
            $dashboardKey,
            $context->organization->id,
            $context->workspace?->id,
        );
    }

    public function saveView(TenantContext $context, string $moduleKey, string $dashboardKey, DashboardView $view): DashboardView
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        $view = new DashboardView(
            name: $view->name,
            publicId: $view->publicId,
            organizationId: $context->organization->id,
            workspaceId: $context->workspace?->id,
            dashboardDefinitionId: $view->dashboardDefinitionId,
            layout: $view->layout,
            filters: $view->filters,
            isDefault: $view->isDefault,
            metadata: $view->metadata,
        );

        return $this->viewService->saveView($moduleKey, $dashboardKey, $view);
    }

    public function setDefaultView(TenantContext $context, string $viewPublicId): DashboardView
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->viewService->setDefaultView(
            $viewPublicId,
            $context->organization->id,
            $context->workspace?->id,
        );
    }

    public function deleteViewByPublicId(TenantContext $context, string $viewPublicId): void
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        $this->viewService->deleteView(
            $viewPublicId,
            $context->organization->id,
            $context->workspace?->id,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listActivity(TenantContext $context, string $dashboardPublicId): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->activityService->listForDashboard(
            $context->organization->id,
            $context->workspace?->id,
            $dashboardPublicId,
        );
    }

    public function health(TenantContext $context): DashboardHealthReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->healthService->health($context);
    }

    public function statistics(TenantContext $context): DashboardStatistics
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->statisticsService->statisticsForScope(
            $context->organization,
            $context->workspace,
        );
    }

    private function requireCapability(TenantContext $context): void
    {
        $this->runtimeBridge->requireCapability($context, 'dashboards');
    }

    private function assertRead(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'dashboards.read')) {
            throw new HttpException(403, 'You do not have permission to read dashboards.');
        }
    }

    private function assertManage(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'dashboards.manage')) {
            throw new HttpException(403, 'You do not have permission to manage dashboards.');
        }
    }

    private function assertRender(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'dashboards.render')) {
            throw new HttpException(403, 'You do not have permission to render dashboards.');
        }
    }
}
