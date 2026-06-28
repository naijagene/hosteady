<?php

namespace App\Modules\Sdk\Dashboard\Contracts;

use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;

interface DashboardRenderer
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function render(DashboardDefinition $definition, array $context = []): array;
}
