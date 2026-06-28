<?php

namespace App\Modules\Sdk\Rules\Contracts;

interface RuleEvaluator
{
    public function evaluate(\App\Modules\Sdk\Rules\Data\RuleDefinition $rule, \App\Modules\Sdk\Rules\Data\RuleContext $context): \App\Modules\Sdk\Rules\Data\RuleEvaluationResult;
}
