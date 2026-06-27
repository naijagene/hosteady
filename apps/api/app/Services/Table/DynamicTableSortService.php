<?php

namespace App\Services\Table;

use App\Modules\Sdk\Table\Contracts\TableSortResolver;
use App\Modules\Sdk\Table\Data\TableDefinition;
use App\Modules\Sdk\Table\Data\TableSort;
use App\Modules\Sdk\Table\Enums\TableSortDirection;

class DynamicTableSortService implements TableSortResolver
{
    /**
     * @param  list<TableSort>  $requested
     * @return list<TableSort>
     */
    public function resolve(TableDefinition $definition, array $requested = []): array
    {
        if ($requested === []) {
            return $definition->defaultSort !== null ? [$definition->defaultSort] : [];
        }

        $sortableKeys = [];
        foreach ($definition->columns as $column) {
            if ($column->sortable) {
                $sortableKeys[$column->key] = true;
            }
        }

        $resolved = [];
        foreach ($requested as $sort) {
            if ($sort->columnKey === '' || ! isset($sortableKeys[$sort->columnKey])) {
                continue;
            }

            $direction = strtolower($sort->direction) === TableSortDirection::Desc->value
                ? TableSortDirection::Desc->value
                : TableSortDirection::Asc->value;

            $resolved[] = new TableSort(
                columnKey: $sort->columnKey,
                direction: $direction,
                metadata: $sort->metadata,
            );
        }

        if ($resolved === [] && $definition->defaultSort !== null) {
            return [$definition->defaultSort];
        }

        return $resolved;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<TableSort>  $sorts
     * @return list<array<string, mixed>>
     */
    public function apply(array $rows, array $sorts): array
    {
        if ($sorts === []) {
            return $rows;
        }

        usort($rows, function (array $left, array $right) use ($sorts): int {
            foreach ($sorts as $sort) {
                $leftValue = $left[$sort->columnKey] ?? null;
                $rightValue = $right[$sort->columnKey] ?? null;
                $comparison = $this->compareValues($leftValue, $rightValue);

                if ($comparison !== 0) {
                    return $sort->direction === TableSortDirection::Desc->value ? -$comparison : $comparison;
                }
            }

            return 0;
        });

        return $rows;
    }

    private function compareValues(mixed $left, mixed $right): int
    {
        if (is_numeric($left) && is_numeric($right)) {
            return $left <=> $right;
        }

        return strcmp((string) $left, (string) $right);
    }
}
