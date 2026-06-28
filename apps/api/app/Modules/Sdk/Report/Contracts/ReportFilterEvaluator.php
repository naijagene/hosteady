<?php

namespace App\Modules\Sdk\Report\Contracts;

use App\Modules\Sdk\Report\Data\ReportFilter;

interface ReportFilterEvaluator
{
    public function evaluate(ReportFilter $filter, mixed $value): bool;

    /**
     * @param  list<ReportFilter>  $filters
     * @param  array<string, mixed>  $row
     */
    public function matchesAll(array $filters, array $row): bool;
}
