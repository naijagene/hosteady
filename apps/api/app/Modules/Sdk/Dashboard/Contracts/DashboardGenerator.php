<?php

namespace App\Modules\Sdk\Dashboard\Contracts;

use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;

interface DashboardGenerator
{
    public function generate(string $moduleKey, string $entityKey): DashboardDefinition;

    public function generateEntityDashboard(string $moduleKey, string $entityKey): DashboardDefinition;
}
