<?php

namespace App\Services\Rules;

use App\Modules\Sdk\Rules\Contracts\RuleConditionEvaluator;
use App\Modules\Sdk\Rules\Data\RuleCondition;
use App\Modules\Sdk\Rules\Enums\RuleConditionOperator;

class RuleConditionEvaluatorService implements RuleConditionEvaluator
{
    public function evaluate(RuleCondition $condition, array $facts): bool
    {
        $fieldValue = $this->resolveFieldValue($facts, $condition->field);
        $operator = RuleConditionOperator::from($condition->operator);
        $expected = $condition->value;

        return match ($operator) {
            RuleConditionOperator::Equals => (string) $fieldValue === (string) $expected,
            RuleConditionOperator::NotEquals => (string) $fieldValue !== (string) $expected,
            RuleConditionOperator::Contains => is_string($fieldValue) && str_contains($fieldValue, (string) $expected),
            RuleConditionOperator::NotContains => ! is_string($fieldValue) || ! str_contains($fieldValue, (string) $expected),
            RuleConditionOperator::GreaterThan => is_numeric($fieldValue) && is_numeric($expected) && (float) $fieldValue > (float) $expected,
            RuleConditionOperator::GreaterThanOrEqual => is_numeric($fieldValue) && is_numeric($expected) && (float) $fieldValue >= (float) $expected,
            RuleConditionOperator::LessThan => is_numeric($fieldValue) && is_numeric($expected) && (float) $fieldValue < (float) $expected,
            RuleConditionOperator::LessThanOrEqual => is_numeric($fieldValue) && is_numeric($expected) && (float) $fieldValue <= (float) $expected,
            RuleConditionOperator::Between => $this->evaluateBetween($fieldValue, $expected),
            RuleConditionOperator::In => $this->evaluateIn($fieldValue, $expected, false),
            RuleConditionOperator::NotIn => $this->evaluateIn($fieldValue, $expected, true),
            RuleConditionOperator::IsEmpty => $this->isEmpty($fieldValue),
            RuleConditionOperator::IsNotEmpty => ! $this->isEmpty($fieldValue),
            RuleConditionOperator::StartsWith => is_string($fieldValue) && str_starts_with($fieldValue, (string) $expected),
            RuleConditionOperator::EndsWith => is_string($fieldValue) && str_ends_with($fieldValue, (string) $expected),
            RuleConditionOperator::Regex => $this->evaluateRegex($fieldValue, $expected),
        };
    }

    public function evaluateAll(array $conditions, array $facts, string $logic = 'and'): bool
    {
        if ($conditions === []) {
            return true;
        }

        $results = [];
        foreach ($conditions as $conditionData) {
            $condition = $conditionData instanceof RuleCondition
                ? $conditionData
                : RuleCondition::fromArray(is_array($conditionData) ? $conditionData : []);
            $results[] = $this->evaluate($condition, $facts);
        }

        if ($logic === 'or') {
            return in_array(true, $results, true);
        }

        return ! in_array(false, $results, true);
    }

    private function resolveFieldValue(array $facts, string $field): mixed
    {
        if (array_key_exists($field, $facts)) {
            return $facts[$field];
        }

        foreach (explode('.', $field) as $segment) {
            if (! is_array($facts) || ! array_key_exists($segment, $facts)) {
                return null;
            }
            $facts = $facts[$segment];
        }

        return $facts;
    }

    private function evaluateBetween(mixed $fieldValue, mixed $expected): bool
    {
        if (! is_numeric($fieldValue) || ! is_array($expected) || count($expected) < 2) {
            return false;
        }

        return (float) $fieldValue >= (float) $expected[0] && (float) $fieldValue <= (float) $expected[1];
    }

    private function evaluateIn(mixed $fieldValue, mixed $expected, bool $negate): bool
    {
        $haystack = is_array($expected) ? $expected : [$expected];
        $found = in_array($fieldValue, $haystack, true) || in_array((string) $fieldValue, array_map('strval', $haystack), true);

        return $negate ? ! $found : $found;
    }

    private function evaluateRegex(mixed $fieldValue, mixed $expected): bool
    {
        if (! is_string($fieldValue) || ! is_string($expected) || $expected === '') {
            return false;
        }

        $pattern = str_starts_with($expected, '/') ? $expected : '/'.str_replace('/', '\\/', $expected).'/';

        return @preg_match($pattern, $fieldValue) === 1;
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [] ;
    }
}
