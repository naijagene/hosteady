<?php

namespace App\Services\Rules;

use App\Support\Tenant\TenantContext;

class RuleDocumentBridge
{
    public function evaluateDocumentEventBestEffort(TenantContext $context, string $eventName, array $facts = []): void
    {
        try {
            if (! (bool) config('heos.enterprise.business_rules.enabled', true)) {
                return;
            }

            app(EnterpriseRuleEngineService::class)->evaluate($context, \App\Modules\Sdk\Rules\Data\RuleEvaluationRequest::fromArray([
                'trigger_type' => $eventName,
                'facts' => $facts,
                'metadata' => ['source' => 'document'],
            ]));
        } catch (\Throwable) {
        }
    }
}
