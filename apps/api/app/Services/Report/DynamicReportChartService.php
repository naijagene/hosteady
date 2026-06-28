<?php

namespace App\Services\Report;

use App\Modules\Sdk\Report\Data\ReportChart;

class DynamicReportChartService
{
    /**
     * @param  list<ReportChart>  $charts
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public function resolveAll(array $charts, array $rows): array
    {
        return array_map(fn (ReportChart $chart) => $this->resolve($chart, $rows), $charts);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public function resolve(ReportChart $chart, array $rows): array
    {
        return array_merge($chart->toArray(), [
            'data' => [],
            'metadata' => array_merge($chart->metadata, [
                'placeholder' => true,
                'row_count' => count($rows),
            ]),
        ]);
    }
}
