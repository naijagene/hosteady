<?php

namespace App\Services\Dashboard;

use App\Modules\Sdk\Dashboard\Contracts\DashboardLayoutProvider;
use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;
use App\Modules\Sdk\Dashboard\Data\DashboardLayout;
use App\Modules\Sdk\Dashboard\Data\DashboardLayoutItem;

class DynamicDashboardLayoutService implements DashboardLayoutProvider
{
    public function resolve(DashboardDefinition $definition, array $context = []): DashboardLayout
    {
        if ($definition->layout !== null) {
            return $definition->layout;
        }

        $items = [];
        foreach ($definition->widgets as $index => $widget) {
            $layout = $widget->layout ?? new DashboardLayoutItem(
                widgetKey: $widget->widgetKey,
                x: ($index % 3) * 4,
                y: intdiv($index, 3) * 2,
                width: 4,
                height: 2,
            );
            $items[] = $layout;
        }

        return new DashboardLayout(items: $items);
    }
}
