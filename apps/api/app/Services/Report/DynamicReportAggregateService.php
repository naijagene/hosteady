<?php

namespace App\Services\Report;

use App\Modules\Sdk\Report\Contracts\ReportAggregateResolver;
use App\Modules\Sdk\Report\Data\ReportAggregate;
use App\Modules\Sdk\Report\Enums\ReportAggregateFunction;

class DynamicReportAggregateService implements ReportAggregateResolver
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{value: mixed, warnings: list<string>}
     */
    public function resolve(ReportAggregate $aggregate, array $rows): array
    {
        $warnings = [];
        $fieldKey = $aggregate->fieldKey;

        return match ($aggregate->function) {
            ReportAggregateFunction::Count->value => ['value' => count($rows), 'warnings' => $warnings],
            ReportAggregateFunction::Sum->value => $this->numericAggregate($rows, $fieldKey, 'sum', $warnings),
            ReportAggregateFunction::Avg->value => $this->numericAggregate($rows, $fieldKey, 'avg', $warnings),
            ReportAggregateFunction::Min->value => $this->numericAggregate($rows, $fieldKey, 'min', $warnings),
            ReportAggregateFunction::Max->value => $this->numericAggregate($rows, $fieldKey, 'max', $warnings),
            ReportAggregateFunction::DistinctCount->value => $this->distinctCount($rows, $fieldKey, $warnings),
            default => [
                'value' => null,
                'warnings' => [sprintf('Unsupported aggregate function [%s].', $aggregate->function)],
            ],
        };
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $warnings
     * @return array{value: mixed, warnings: list<string>}
     */
    private function numericAggregate(array $rows, ?string $fieldKey, string $operation, array &$warnings): array
    {
        if ($fieldKey === null || $fieldKey === '') {
            $warnings[] = sprintf('Field key is required for [%s] aggregate.', $operation);

            return ['value' => null, 'warnings' => $warnings];
        }

        $values = [];
        foreach ($rows as $row) {
            $value = $row[$fieldKey] ?? null;
            if (is_numeric($value)) {
                $values[] = (float) $value;
            }
        }

        if ($values === []) {
            $warnings[] = sprintf('No numeric values found for field [%s].', $fieldKey);

            return ['value' => 0, 'warnings' => $warnings];
        }

        $result = match ($operation) {
            'sum' => array_sum($values),
            'avg' => array_sum($values) / count($values),
            'min' => min($values),
            'max' => max($values),
            default => null,
        };

        return ['value' => $result, 'warnings' => $warnings];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $warnings
     * @return array{value: mixed, warnings: list<string>}
     */
    private function distinctCount(array $rows, ?string $fieldKey, array &$warnings): array
    {
        if ($fieldKey === null || $fieldKey === '') {
            $warnings[] = 'Field key is required for distinct_count aggregate.';

            return ['value' => null, 'warnings' => $warnings];
        }

        $distinct = [];
        foreach ($rows as $row) {
            $distinct[(string) ($row[$fieldKey] ?? '')] = true;
        }

        return ['value' => count($distinct), 'warnings' => $warnings];
    }
}
