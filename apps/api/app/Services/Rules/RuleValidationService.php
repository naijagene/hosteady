<?php

namespace App\Services\Rules;

use App\Modules\Sdk\Rules\Data\RuleDefinition;
use App\Modules\Sdk\Rules\Data\RuleSetDefinition;
use App\Modules\Sdk\Rules\Exceptions\RuleValidationException;

class RuleValidationService
{
    public function validateRuleSet(RuleSetDefinition $definition): void
    {
        if ($definition->name === '') {
            throw new RuleValidationException('Rule set name is required.');
        }
    }

    public function validateRuleDefinition(RuleDefinition $definition): void
    {
        if ($definition->name === '') {
            throw new RuleValidationException('Rule definition name is required.');
        }

        if ($definition->ruleSetPublicId === '') {
            throw new RuleValidationException('Rule set public id is required.');
        }
    }
}
