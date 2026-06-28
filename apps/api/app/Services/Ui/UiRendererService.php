<?php

namespace App\Services\Ui;

use App\Modules\Sdk\Ui\Contracts\UiRenderer;
use App\Modules\Sdk\Ui\Data\UiComponent;
use App\Modules\Sdk\Ui\Data\UiPageDefinition;
use App\Modules\Sdk\Ui\Data\UiRenderPayload;
use App\Modules\Sdk\Ui\Enums\UiBindingType;
use App\Modules\Sdk\Ui\Exceptions\UiRenderException;
use App\Support\Tenant\TenantContext;

class UiRendererService implements UiRenderer
{
    public function __construct(
        private readonly UiPageRegistryService $pageRegistry,
        private readonly UiConditionEvaluatorService $conditionEvaluator,
        private readonly UiActionService $actionService,
        private readonly UiThemeService $themeService,
        private readonly UiPersonalizationService $personalizationService,
        private readonly UiPermissionBridge $permissionBridge,
        private readonly UiAuditRecorder $auditRecorder,
        private readonly UiFormBridge $formBridge,
        private readonly UiTableBridge $tableBridge,
        private readonly UiDashboardBridge $dashboardBridge,
        private readonly UiReportBridge $reportBridge,
        private readonly UiApplicationBridge $applicationBridge,
        private readonly UiWorkflowBridge $workflowBridge,
        private readonly UiTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function render(TenantContext $context, string $moduleKey, string $pageKey): UiRenderPayload
    {
        $permissions = $this->permissionBridge->renderPermissions($context);

        if (! $this->tableHealthSupport->coreTablesPresent()) {
            return $this->tableHealthSupport->emptyRenderPayload($context, $permissions, $moduleKey, $pageKey);
        }

        $page = $this->pageRegistry->findByKey(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $pageKey,
        );

        if (! $this->conditionEvaluator->evaluate($context, $page->conditions)) {
            throw new UiRenderException(sprintf('UI page [%s.%s] is not visible for the current context.', $moduleKey, $pageKey));
        }

        $theme = $this->themeService->themeForPage($page);
        $personalization = $this->personalizationService->get($context, $page->publicId);
        $components = $this->resolveComponents($context, $page);
        $payload = new UiRenderPayload(
            page: $page->toArray(),
            layout: $page->layout,
            regions: $page->regions,
            components: $components,
            actions: $this->actionService->pageActions($page),
            conditions: $page->conditions,
            breakpoints: $page->breakpoints,
            theme: $theme->toArray(),
            personalization: $personalization->personalization,
            permissions: $this->permissionBridge->renderPermissions($context),
            runtimeContext: $this->buildRuntimeContext($context, $page),
        );

        $this->auditRecorder->recordRendered($page->publicId, $context);

        return $payload;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function resolveComponents(TenantContext $context, UiPageDefinition $page): array
    {
        $resolved = [];

        foreach ($page->components as $componentData) {
            if (! is_array($componentData)) {
                continue;
            }

            $component = UiComponent::fromArray($componentData);

            if (! $this->conditionEvaluator->evaluate($context, $component->conditions)) {
                continue;
            }

            $payload = $component->toArray();
            $binding = $this->resolveBinding($component->bindingType, $component->moduleKey, $component->bindingConfig);

            if ($binding !== null) {
                $payload['resolved_binding'] = $binding;
            }

            $payload['actions'] = $this->actionService->componentActions($component);
            $resolved[] = $payload;
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>|null
     */
    private function resolveBinding(
        ?string $bindingType,
        ?string $moduleKey,
        array $config,
    ): ?array {
        if ($bindingType === null || $bindingType === '') {
            return null;
        }

        $resourceKey = (string) ($config['resource_key'] ?? '');

        return match ($bindingType) {
            UiBindingType::Form->value => $this->formBridge->resolveReferenceBestEffort($moduleKey, $resourceKey, $config),
            UiBindingType::Table->value => $this->tableBridge->resolveReferenceBestEffort($moduleKey, $resourceKey, $config),
            UiBindingType::Dashboard->value => $this->dashboardBridge->resolveReferenceBestEffort($moduleKey, $resourceKey, $config),
            UiBindingType::Report->value => $this->reportBridge->resolveReferenceBestEffort($moduleKey, $resourceKey, $config),
            UiBindingType::Workflow->value => $this->workflowBridge->resolveReferenceBestEffort($moduleKey, $resourceKey, $config),
            UiBindingType::Entity->value => $this->resolveStaticBinding($config),
            UiBindingType::Document->value, UiBindingType::Notification->value, UiBindingType::Static->value, UiBindingType::Custom->value => $this->resolveStaticBinding($config),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>|null
     */
    private function resolveStaticBinding(array $config): ?array
    {
        if ($config === []) {
            return null;
        }

        return ['config' => $config];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRuntimeContext(TenantContext $context, UiPageDefinition $page): array
    {
        $application = $page->applicationPublicId !== null
            ? $this->applicationBridge->resolveReferenceBestEffort($page->moduleKey, $page->applicationPublicId)
            : null;

        return [
            'organization_public_id' => $context->organizationPublicId,
            'workspace_public_id' => $context->workspacePublicId,
            'membership_public_id' => $context->membershipPublicId,
            'module_key' => $page->moduleKey,
            'page_key' => $page->pageKey,
            'application_public_id' => $page->applicationPublicId,
            'application' => $application,
            'capabilities' => is_array($context->membership->metadata ?? null) ? $context->membership->metadata : [],
        ];
    }
}
