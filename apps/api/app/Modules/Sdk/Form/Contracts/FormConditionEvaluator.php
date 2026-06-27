<?php

namespace App\Modules\Sdk\Form\Contracts;

use App\Modules\Sdk\Form\Data\FormCondition;

interface FormConditionEvaluator
{
    public function evaluate(FormCondition $condition, array $values, array $context = []): bool;

    /**
     * @param  list<FormCondition>  $conditions
     */
    public function evaluateAll(array $conditions, array $values, array $context = []): bool;
}
