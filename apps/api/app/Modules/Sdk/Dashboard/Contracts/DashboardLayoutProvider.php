<?php

namespace App\Modules\Sdk\Dashboard\Contracts;

use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;
use App\Modules\Sdk\Dashboard\Data\DashboardLayout;

interface DashboardLayoutProvider
{
    public function resolve(DashboardDefinition $definition, array $context = []): DashboardLayout;
}
