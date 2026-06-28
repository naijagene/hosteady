<?php

namespace App\Modules\Sdk\Report\Contracts;

use App\Modules\Sdk\Report\Data\ReportAggregate;

interface ReportAggregateResolver
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{value: mixed, warnings: list<string>}
     */
    public function resolve(ReportAggregate $aggregate, array $rows): array;
}
