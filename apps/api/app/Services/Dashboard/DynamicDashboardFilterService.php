<?php

namespace App\Services\Dashboard;

use App\Modules\Sdk\Dashboard\Contracts\DashboardFilterEvaluator;
use App\Modules\Sdk\Dashboard\Data\DashboardFilter;
use App\Modules\Sdk\Dashboard\Enums\DashboardFilterOperator;

class DynamicDashboardFilterService implements DashboardFilterEvaluator
{
    public function evaluate(DashboardFilter $filter, mixed $value): bool
    {
        return match ($filter->operator) {
            DashboardFilterOperator::Equals->value => $this->normalize($value) === $this->normalize($filter->value),
            DashboardFilterOperator::NotEquals->value => $this->normalize($value) !== $this->normalize($filter->value),
            DashboardFilterOperator::Contains->value => $this->contains($value, $filter->value),
            DashboardFilterOperator::GreaterThan->value => $this->compare($value, $filter->value) > 0,
            DashboardFilterOperator::LessThan->value => $this->compare($value, $filter->value) < 0,
            DashboardFilterOperator::Between->value => $this->between($value, $filter->value),
            DashboardFilterOperator::IsEmpty->value => $this->isEmpty($value),
            DashboardFilterOperator::IsNotEmpty->value => ! $this->isEmpty($value),
            default => true,
        };
    }

    /**
     * @param  list<DashboardFilter>  $filters
     * @param  array<string, mixed>  $row
     */
    public function matchesAll(array $filters, array $row): bool
    {
        foreach ($filters as $filter) {
            $value = $row[$filter->fieldKey] ?? null;

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

    private function between(mixed $value, mixed $range): bool
    {
        if (! is_array($range) || count($range) < 2) {
            return false;
        }

        return $this->compare($value, $range[0]) >= 0 && $this->compare($value, $range[1]) <= 0;
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && $value === []);
    }
}
