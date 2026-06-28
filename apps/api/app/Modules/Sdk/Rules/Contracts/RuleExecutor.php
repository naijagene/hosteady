<?php

namespace App\Modules\Sdk\Rules\Contracts;

interface RuleExecutor
{
    public function execute(\App\Modules\Sdk\Rules\Data\RuleDefinition $rule, \App\Modules\Sdk\Rules\Data\RuleContext $context): \App\Modules\Sdk\Rules\Data\RuleExecutionResult;
}
