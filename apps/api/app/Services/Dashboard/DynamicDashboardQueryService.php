<?php

namespace App\Services\Dashboard;

use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;

class DynamicDashboardQueryService
{
    public function __construct(
        private readonly DynamicDashboardFilterService $filterService,
        private readonly DynamicDashboardDataProviderService $dataProviderService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function query(DashboardDefinition $definition, array $filters = [], array $context = []): array
    {
        $widgetData = $this->dataProviderService->resolveAll($definition, $context);

        return [
            'module_key' => $definition->moduleKey,
            'dashboard_key' => $definition->dashboardKey,
            'widget_data' => array_map(fn ($data) => $data->toArray(), $widgetData),
            'filters_applied' => count($filters),
            'metadata' => ['placeholder' => true],
        ];
    }
}
