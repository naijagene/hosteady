<?php

namespace App\Services\Table;

use App\Modules\Sdk\Table\Contracts\TableFilterEvaluator;
use App\Modules\Sdk\Table\Data\TableFilter;
use App\Modules\Sdk\Table\Enums\TableFilterOperator;

class DynamicTableFilterEvaluator implements TableFilterEvaluator
{
    public function evaluate(TableFilter $filter, mixed $value): bool
    {
        return match ($filter->operator) {
            TableFilterOperator::Equals->value => $this->normalize($value) === $this->normalize($filter->value),
            TableFilterOperator::NotEquals->value => $this->normalize($value) !== $this->normalize($filter->value),
            TableFilterOperator::Contains->value => $this->contains($value, $filter->value),
            TableFilterOperator::GreaterThan->value => $this->compare($value, $filter->value) > 0,
            TableFilterOperator::LessThan->value => $this->compare($value, $filter->value) < 0,
            TableFilterOperator::IsEmpty->value => $this->isEmpty($value),
            TableFilterOperator::IsNotEmpty->value => ! $this->isEmpty($value),
            default => true,
        };
    }

    /**
     * @param  list<TableFilter>  $filters
     * @param  array<string, mixed>  $row
     */
    public function matchesAll(array $filters, array $row): bool
    {
        foreach ($filters as $filter) {
            $value = $row[$filter->columnKey] ?? null;

            if (! $this->evaluate($filter, $value)) {
                return false;
            }
        }

        return true;
    }

    private function normalize(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return strtolower(trim((string) $value));
    }

    private function contains(mixed $value, mixed $needle): bool
    {
        if ($value === null || $needle === null) {
            return false;
        }

        return str_contains(strtolower((string) $value), strtolower((string) $needle));
    }

    private function compare(mixed $left, mixed $right): int
    {
        if (is_numeric($left) && is_numeric($right)) {
            return $left <=> $right;
        }

        return strcmp((string) $left, (string) $right);
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && $value === []);
    }
}
