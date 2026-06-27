<?php

namespace App\Services\Form;

use App\Modules\Sdk\Form\Contracts\FormConditionEvaluator;
use App\Modules\Sdk\Form\Data\FormCondition;

class DynamicFormConditionEvaluator implements FormConditionEvaluator
{
    public function evaluate(FormCondition $condition, array $values, array $context = []): bool
    {
        $actual = $values[$condition->field] ?? $context[$condition->field] ?? null;
        $expected = $condition->value;

        return match ($condition->operator) {
            'equals', 'eq' => $this->normalize($actual) === $this->normalize($expected),
            'not_equals', 'neq' => $this->normalize($actual) !== $this->normalize($expected),
            'contains' => $this->contains($actual, $expected),
            'greater_than', 'gt' => $this->compare($actual, $expected) > 0,
            'less_than', 'lt' => $this->compare($actual, $expected) < 0,
            'is_empty' => $this->isEmpty($actual),
            'is_not_empty' => ! $this->isEmpty($actual),
            default => false,
        };
    }

    public function evaluateAll(array $conditions, array $values, array $context = []): bool
    {
        if ($conditions === []) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (! $this->evaluate($condition, $values, $context)) {
                return false;
            }
        }

        return true;
    }

    private function normalize(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            return strtolower(trim($value));
        }

        return $value;
    }

    private function contains(mixed $actual, mixed $expected): bool
    {
        if (is_array($actual)) {
            return in_array($expected, $actual, true)
                || (is_string($expected) && in_array($expected, array_map('strval', $actual), true));
        }

        if (is_string($actual) && (is_string($expected) || is_numeric($expected))) {
            return str_contains(strtolower($actual), strtolower((string) $expected));
        }

        return false;
    }

    private function compare(mixed $actual, mixed $expected): int
    {
        if (! is_numeric($actual) || ! is_numeric($expected)) {
            return strcmp((string) $actual, (string) $expected);
        }

        return $actual <=> $expected;
    }

    private function isEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        return is_array($value) && $value === [];
    }
}
