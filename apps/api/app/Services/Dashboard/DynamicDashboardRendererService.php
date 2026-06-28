<?php

namespace App\Services\Dashboard;

use App\Modules\Sdk\Dashboard\Contracts\DashboardRenderer;
use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;
use App\Modules\Sdk\Dashboard\Data\DashboardReference;
use App\Support\Tenant\TenantContext;

class DynamicDashboardRendererService implements DashboardRenderer
{
    public function __construct(
        private readonly DynamicDashboardLayoutService $layoutService,
        private readonly DynamicDashboardDataProviderService $dataProviderService,
        private readonly DynamicDashboardActionService $actionService,
        private readonly DynamicDashboardAuditRecorder $auditRecorder,
    ) {
    }

    public function render(DashboardDefinition $definition, array $context = []): array
    {
        $permissions = is_array($context['permissions'] ?? null) ? $context['permissions'] : $this->defaultPermissions();
        $runtimeContext = $this->buildRuntimeContext($context);
        $layout = $this->layoutService->resolve($definition, $context);
        $widgetData = $this->dataProviderService->resolveAll($definition, $context);
        $actions = $this->actionService->resolve($definition, $context);

        $payload = [
            'metadata' => array_merge($definition->metadata, [
                'module_key' => $definition->moduleKey,
                'dashboard_key' => $definition->dashboardKey,
                'public_id' => $definition->publicId,
                'name' => $definition->name,
                'description' => $definition->description,
                'type' => $definition->type,
                'status' => $definition->status,
                'visibility' => $definition->visibility,
            ]),
            'layout' => $layout->toArray(),
            'widgets' => array_map(fn ($widget) => $widget->toArray(), $definition->widgets),
            'widget_data' => array_map(fn ($data) => $data->toArray(), $widgetData),
            'filters' => array_map(fn ($filter) => $filter->toArray(), $definition->filters),
            'actions' => array_map(fn ($action) => $action->toArray(), $actions),
            'runtime_context' => $runtimeContext,
            'permissions' => $permissions,
            'entity_reference' => $this->entityReference($definition),
        ];

        $this->auditRecorder->recordRendered($definition);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRuntimeContext(array $context): array
    {
        $runtime = [
            'view_id' => $context['view_id'] ?? null,
            'mode' => $context['mode'] ?? 'default',
        ];

        if (app()->bound(TenantContext::class)) {
            $tenant = app(TenantContext::class);
            $runtime['organization_public_id'] = $tenant->organizationPublicId;
            $runtime['workspace_public_id'] = $tenant->workspacePublicId;
            $runtime['user_public_id'] = $tenant->user->public_id ?? null;
        }

        return array_merge($runtime, is_array($context['runtime'] ?? null) ? $context['runtime'] : []);
    }

    /**
     * @return array<string, bool>
     */
    private function defaultPermissions(): array
    {
        return [
            'read' => true,
            'render' => true,
            'export' => true,
            'manage' => false,
        ];
    }

    private function entityReference(DashboardDefinition $definition): ?array
    {
        if ($definition->entityKey === null) {
            return null;
        }

        return (new DashboardReference(
            moduleKey: $definition->moduleKey,
            dashboardKey: $definition->dashboardKey,
            publicId: $definition->publicId,
            entityKey: $definition->entityKey,
            label: $definition->name,
            organizationId: $definition->organizationId,
            workspaceId: $definition->workspaceId,
        ))->toArray();
    }
}
