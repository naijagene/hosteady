<?php

namespace App\Services\Report;

use App\Modules\Sdk\Report\Contracts\ReportFilterEvaluator;
use App\Modules\Sdk\Report\Data\ReportFilter;
use App\Modules\Sdk\Report\Enums\ReportFilterOperator;

class DynamicReportFilterService implements ReportFilterEvaluator
{
    public function evaluate(ReportFilter $filter, mixed $value): bool
    {
        return match ($filter->operator) {
            ReportFilterOperator::Equals->value => $this->normalize($value) === $this->normalize($filter->value),
            ReportFilterOperator::NotEquals->value => $this->normalize($value) !== $this->normalize($filter->value),
            ReportFilterOperator::Contains->value => $this->contains($value, $filter->value),
            ReportFilterOperator::GreaterThan->value => $this->compare($value, $filter->value) > 0,
            ReportFilterOperator::LessThan->value => $this->compare($value, $filter->value) < 0,
            ReportFilterOperator::Between->value => $this->between($value, $filter->value),
            ReportFilterOperator::IsEmpty->value => $this->isEmpty($value),
            ReportFilterOperator::IsNotEmpty->value => ! $this->isEmpty($value),
            default => true,
        };
    }

    /**
     * @param  list<ReportFilter>  $filters
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
        return $value === null || $value === '' || $value === [];
    }
}
