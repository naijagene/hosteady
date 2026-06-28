<?php

namespace App\Services\Report;

use App\Modules\Sdk\Report\Data\ReportDefinition;
use App\Modules\Sdk\Report\Data\ReportFilter;

class DynamicReportQueryService
{
    public function __construct(
        private readonly DynamicReportFilterService $filterService,
    ) {
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public function applyFilters(ReportDefinition $definition, array $rows, array $context = []): array
    {
        $filters = $definition->filters;

        if (isset($context['filters']) && is_array($context['filters'])) {
            foreach ($context['filters'] as $filterData) {
                if (is_array($filterData)) {
                    $filters[] = ReportFilter::fromArray($filterData);
                }
            }
        }

        if ($filters === []) {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            fn (array $row) => $this->filterService->matchesAll($filters, $row),
        ));
    }
}
