<?php

namespace App\Services\Dashboard;

use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;
use App\Modules\Sdk\Dashboard\Exceptions\DashboardValidationException;

class DynamicDashboardValidationService
{
    public function validate(DashboardDefinition $definition): bool
    {
        $this->assertValid($definition);

        return true;
    }

    public function assertValid(DashboardDefinition $definition): void
    {
        if ($definition->moduleKey === '') {
            throw new DashboardValidationException('Module key is required.');
        }

        if ($definition->dashboardKey === '') {
            throw new DashboardValidationException('Dashboard key is required.');
        }

        if ($definition->name === '') {
            throw new DashboardValidationException('Dashboard name is required.');
        }

        if (! preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $definition->moduleKey)) {
            throw new DashboardValidationException('Module key format is invalid.');
        }

        if (! preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $definition->dashboardKey)) {
            throw new DashboardValidationException('Dashboard key format is invalid.');
        }

        $widgetKeys = [];
        foreach ($definition->widgets as $widget) {
            if ($widget->widgetKey === '') {
                throw new DashboardValidationException('Widget key is required.');
            }

            if (isset($widgetKeys[$widget->widgetKey])) {
                throw new DashboardValidationException(sprintf('Duplicate widget key [%s].', $widget->widgetKey));
            }

            $widgetKeys[$widget->widgetKey] = true;
        }
    }
}
