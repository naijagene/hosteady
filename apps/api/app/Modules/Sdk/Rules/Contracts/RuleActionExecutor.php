<?php

namespace App\Modules\Sdk\Rules\Contracts;

interface RuleActionExecutor
{
    /** @param list<\App\Modules\Sdk\Rules\Data\RuleAction> $actions */
    public function execute(array $actions, array $facts, \App\Modules\Sdk\Rules\Data\RuleDefinition $rule): array;
}
