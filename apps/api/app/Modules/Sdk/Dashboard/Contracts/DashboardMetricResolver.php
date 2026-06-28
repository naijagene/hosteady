<?php

namespace App\Modules\Sdk\Dashboard\Contracts;

use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;
use App\Modules\Sdk\Dashboard\Data\DashboardMetric;

interface DashboardMetricResolver
{
    public function resolve(DashboardDefinition $definition, DashboardMetric $metric, array $context = []): mixed;
}
