<?php

namespace App\Services\Rules;

use App\Support\Tenant\TenantContext;

class RuleReportBridge
{
    public function evaluateReportCompletedBestEffort(TenantContext $context, array $facts = []): void
    {
        try {
            if (! (bool) config('heos.enterprise.business_rules.enabled', true)) {
                return;
            }

            app(EnterpriseRuleEngineService::class)->evaluate($context, \App\Modules\Sdk\Rules\Data\RuleEvaluationRequest::fromArray([
                'trigger_type' => 'report_completed',
                'facts' => $facts,
                'metadata' => ['source' => 'report'],
            ]));
        } catch (\Throwable) {
        }
    }
}
