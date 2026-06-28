<?php

namespace App\Modules\Sdk\Dashboard\Contracts;

use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;
use App\Modules\Sdk\Dashboard\Data\DashboardWidget;

interface DashboardWidgetProvider
{
    /**
     * @return list<DashboardWidget>
     */
    public function listWidgets(DashboardDefinition $definition): array;

    public function createWidget(DashboardDefinition $definition, DashboardWidget $widget): DashboardWidget;

    public function updateWidget(DashboardWidget $widget): DashboardWidget;

    public function deleteWidget(string $widgetPublicId): void;
}
