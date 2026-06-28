<?php

namespace App\Modules\Sdk\Dashboard\Contracts;

use App\Modules\Sdk\Dashboard\Data\DashboardFilter;

interface DashboardFilterEvaluator
{
    public function evaluate(DashboardFilter $filter, mixed $value): bool;

    /**
     * @param  list<DashboardFilter>  $filters
     * @param  array<string, mixed>  $row
     */
    public function matchesAll(array $filters, array $row): bool;
}
