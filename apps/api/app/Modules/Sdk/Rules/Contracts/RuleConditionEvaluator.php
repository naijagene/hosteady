<?php

namespace App\Modules\Sdk\Rules\Contracts;

interface RuleConditionEvaluator
{
    public function evaluate(\App\Modules\Sdk\Rules\Data\RuleCondition $condition, array $facts): bool;

    /** @param list<\App\Modules\Sdk\Rules\Data\RuleCondition> $conditions */
    public function evaluateAll(array $conditions, array $facts, string $logic = 'and'): bool;
}
