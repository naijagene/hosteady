<?php

namespace App\Services\Dashboard;

use App\Modules\Sdk\Dashboard\Contracts\DashboardMetricResolver;
use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;
use App\Modules\Sdk\Dashboard\Data\DashboardMetric;

class DynamicDashboardMetricService implements DashboardMetricResolver
{
    public function __construct(
        private readonly DynamicDashboardDataProviderService $dataProviderService,
    ) {
    }

    public function resolve(DashboardDefinition $definition, DashboardMetric $metric, array $context = []): mixed
    {
        foreach ($definition->widgets as $widget) {
            if ($widget->metric !== null && $widget->metric->key === $metric->key) {
                return $this->dataProviderService->resolve($widget, $context)->value;
            }
        }

        return 0;
    }
}
