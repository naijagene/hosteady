<?php

namespace App\Services\Rules;

use App\Support\Tenant\TenantContext;

class RuleWorkflowBridge
{
    public function __construct(
        private readonly EnterpriseRuleEngineService $ruleEngine,
    ) {
    }

    public function evaluateWorkflowEventBestEffort(TenantContext $context, string $eventName, array $facts = []): void
    {
        try {
            if (! (bool) config('heos.enterprise.business_rules.enabled', true)) {
                return;
            }

            $this->ruleEngine->evaluate($context, \App\Modules\Sdk\Rules\Data\RuleEvaluationRequest::fromArray([
                'trigger_type' => $eventName,
                'facts' => $facts,
                'metadata' => ['source' => 'workflow'],
            ]));
        } catch (\Throwable) {
        }
    }
}
