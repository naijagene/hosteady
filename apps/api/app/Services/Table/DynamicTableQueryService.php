<?php

namespace App\Services\Table;

use App\Modules\Sdk\Table\Contracts\TableQueryExecutor;
use App\Modules\Sdk\Table\Data\TableDefinition;
use App\Modules\Sdk\Table\Data\TableQueryRequest;
use App\Modules\Sdk\Table\Data\TableQueryResult;
use App\Modules\Sdk\Table\Data\TableRow;
use App\Services\DataRepository\EnterpriseEntityRecordMapper;
use App\Services\DataRepository\EnterpriseEntityRecordTableBridge;

class DynamicTableQueryService implements TableQueryExecutor
{
    public function __construct(
        private readonly DynamicTableValidationService $validationService,
        private readonly DynamicTableFilterEvaluator $filterEvaluator,
        private readonly DynamicTableSortService $sortService,
        private readonly DynamicTableAuditRecorder $auditRecorder,
        private readonly EnterpriseEntityRecordTableBridge $tableBridge,
    ) {
    }

    public function execute(TableQueryRequest $request, TableDefinition $definition): TableQueryResult
    {
        $this->validationService->assertValidQuery($request, $definition);

        $appliedSorts = $this->sortService->resolve($definition, $request->sorts);
        $rows = $this->fetchRows($definition, $request);
        $rows = $this->applyFilters($rows, $request->filters);

        if ($request->search !== null && $request->search !== '') {
            $rows = $this->applySearch($rows, $request->search, $definition);
        }

        $rows = $this->sortService->apply($rows, $appliedSorts);

        $total = count($rows);
        $totalPages = (int) ceil($total / max(1, $request->perPage));
        $offset = ($request->page - 1) * $request->perPage;
        $pageRows = array_slice($rows, $offset, $request->perPage);

        $result = new TableQueryResult(
            moduleKey: $definition->moduleKey,
            tableKey: $definition->tableKey,
            rows: array_map(
                fn (array $row) => new TableRow(
                    publicId: isset($row['public_id']) ? (string) $row['public_id'] : null,
                    values: $row,
                ),
                $pageRows,
            ),
            total: $total,
            page: $request->page,
            perPage: $request->perPage,
            totalPages: $totalPages,
            appliedFilters: array_map(fn ($filter) => $filter->toArray(), $request->filters),
            appliedSorts: array_map(fn ($sort) => $sort->toArray(), $appliedSorts),
            metadata: ['source' => 'metadata_first_placeholder'],
        );

        $this->auditRecorder->recordQueried($definition, $result);

        return $result;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchRows(TableDefinition $definition, TableQueryRequest $request): array
    {
        if (! app()->bound(\App\Support\Tenant\TenantContext::class)) {
            return [];
        }

        if (! EnterpriseEntityRecordMapper::entityBindingEnabled($definition->metadata)) {
            return [];
        }

        $context = app(\App\Support\Tenant\TenantContext::class);

        return $this->tableBridge->fetchRows(
            $context->organization->id,
            $context->workspace?->id,
            $definition,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<\App\Modules\Sdk\Table\Data\TableFilter>  $filters
     * @return list<array<string, mixed>>
     */
    private function applyFilters(array $rows, array $filters): array
    {
        if ($filters === []) {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            fn (array $row) => $this->filterEvaluator->matchesAll($filters, $row),
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function applySearch(array $rows, string $search, TableDefinition $definition): array
    {
        $searchableKeys = [];
        foreach ($definition->columns as $column) {
            if ($column->searchable) {
                $searchableKeys[] = $column->key;
            }
        }

        if ($searchableKeys === []) {
            return $rows;
        }

        $needle = strtolower($search);

        return array_values(array_filter($rows, function (array $row) use ($searchableKeys, $needle): bool {
            foreach ($searchableKeys as $key) {
                $value = $row[$key] ?? null;
                if ($value !== null && str_contains(strtolower((string) $value), $needle)) {
                    return true;
                }
            }

            return false;
        }));
    }
}
