<?php

namespace App\Modules\Sdk\Dashboard\Contracts;

use App\Modules\Sdk\Dashboard\Data\DashboardWidget;
use App\Modules\Sdk\Dashboard\Data\DashboardWidgetData;

interface DashboardDataProvider
{
    public function resolve(DashboardWidget $widget, array $context = []): DashboardWidgetData;
}
