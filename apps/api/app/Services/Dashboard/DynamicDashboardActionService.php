<?php

namespace App\Services\Dashboard;

use App\Modules\Sdk\Dashboard\Data\DashboardAction;
use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;

class DynamicDashboardActionService
{
    /**
     * @return list<DashboardAction>
     */
    public function resolve(DashboardDefinition $definition, array $context = []): array
    {
        if ($definition->actions !== []) {
            return $definition->actions;
        }

        return [
            new DashboardAction(key: 'refresh', label: 'Refresh', type: 'toolbar'),
            new DashboardAction(key: 'export', label: 'Export', type: 'toolbar'),
        ];
    }
}
