<?php

namespace App\Services\Ui;

use App\Modules\Sdk\Ui\Data\UiComponent;
use App\Modules\Sdk\Ui\Data\UiHealthReport;
use App\Modules\Sdk\Ui\Data\UiLayout;
use App\Modules\Sdk\Ui\Data\UiPageDefinition;
use App\Modules\Sdk\Ui\Data\UiPersonalization;
use App\Modules\Sdk\Ui\Data\UiRenderPayload;
use App\Modules\Sdk\Ui\Data\UiStatistics;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UiDevelopmentService
{
    public function __construct(
        private readonly UiPageDefinitionService $pageDefinitionService,
        private readonly UiLayoutService $layoutService,
        private readonly UiComponentService $componentService,
        private readonly UiRendererService $rendererService,
        private readonly UiRuntimeComposerService $runtimeComposerService,
        private readonly UiPersonalizationService $personalizationService,
        private readonly UiHealthService $healthService,
        private readonly UiStatisticsService $statisticsService,
        private readonly UiPermissionBridge $permissionBridge,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
    ) {
    }

    /** @return list<UiPageDefinition> */
    public function listPages(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->pageDefinitionService->list($context);
    }

    /**
     * @param  UiPageDefinition|array<string, mixed>  $definition
     */
    public function registerPage(TenantContext $context, mixed $definition): UiPageDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->pageDefinitionService->create($context, $definition);
    }

    public function findPage(TenantContext $context, string $moduleKey, string $pageKey): UiPageDefinition
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->pageDefinitionService->find($context, $moduleKey, $pageKey);
    }

    public function renderPage(TenantContext $context, string $moduleKey, string $pageKey): UiRenderPayload
    {
        $this->requireCapability($context);
        $this->assertRender($context);

        return $this->rendererService->render($context, $moduleKey, $pageKey);
    }

    /** @return list<UiLayout> */
    public function listLayouts(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->layoutService->list($context->organization->id, $context->workspace?->id);
    }

    /**
     * @param  UiLayout|array<string, mixed>  $layout
     */
    public function registerLayout(TenantContext $context, mixed $layout): UiLayout
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->layoutService->registerFromSource(
            $context->organization->id,
            $context->workspace?->id,
            null,
            $layout,
        );
    }

    /** @return list<UiComponent> */
    public function listComponents(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->componentService->list($context->organization->id, $context->workspace?->id);
    }

    /**
     * @param  UiComponent|array<string, mixed>  $component
     */
    public function registerComponent(TenantContext $context, mixed $component): UiComponent
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->componentService->registerFromSource(
            $context->organization->id,
            $context->workspace?->id,
            null,
            $component,
        );
    }

    public function composeRuntime(TenantContext $context): UiRenderPayload
    {
        $this->requireCapability($context);
        $this->assertRender($context);

        return $this->runtimeComposerService->compose($context);
    }

    public function health(TenantContext $context): UiHealthReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->healthService->health($context);
    }

    public function statistics(TenantContext $context): UiStatistics
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->statisticsService->statisticsForScope($context->organization, $context->workspace);
    }

    public function updatePersonalization(TenantContext $context, string $pagePublicId, array $personalization): UiPersonalization
    {
        $this->requireCapability($context);
        $this->assertPersonalize($context);

        return $this->personalizationService->update($context, $pagePublicId, $personalization);
    }

    private function requireCapability(TenantContext $context): void
    {
        if (! (bool) config('heos.enterprise.ui_metadata.enabled', true)) {
            throw new HttpException(503, 'UI metadata is disabled.');
        }

        $this->runtimeBridge->requireCapability($context, 'ui_metadata');
    }

    private function assertRead(TenantContext $context): void
    {
        if (! $this->permissionBridge->canRead($context)) {
            throw new HttpException(403, 'You do not have permission to read UI metadata.');
        }
    }

    private function assertManage(TenantContext $context): void
    {
        if (! $this->permissionBridge->canManage($context)) {
            throw new HttpException(403, 'You do not have permission to manage UI metadata.');
        }
    }

    private function assertRender(TenantContext $context): void
    {
        if (! $this->permissionBridge->canRender($context)) {
            throw new HttpException(403, 'You do not have permission to render UI metadata.');
        }
    }

    private function assertPersonalize(TenantContext $context): void
    {
        if (! $this->permissionBridge->canPersonalize($context)) {
            throw new HttpException(403, 'You do not have permission to personalize UI metadata.');
        }
    }
}
