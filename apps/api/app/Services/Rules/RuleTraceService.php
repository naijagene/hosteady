<?php

namespace App\Services\Rules;

use App\Modules\Sdk\Rules\Data\RuleDefinition;
use App\Modules\Sdk\Rules\Data\RuleTrace;

class RuleTraceService
{
    public function trace(RuleDefinition $rule, bool $matched, array $conditionResults, array $actionResults, int $durationMs): RuleTrace
    {
        return new RuleTrace(
            rulePublicId: $rule->publicId,
            matched: $matched,
            conditions: $conditionResults,
            actions: $actionResults,
            durationMs: $durationMs,
        );
    }
}
