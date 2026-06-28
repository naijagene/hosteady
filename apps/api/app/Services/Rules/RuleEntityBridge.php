<?php

namespace App\Services\Rules;

use App\Modules\Sdk\DataRepository\Data\EntityRecordReference;
use App\Support\Tenant\TenantContext;

class RuleEntityBridge
{
    public function __construct(
        private readonly EnterpriseRuleEngineService $ruleEngine,
    ) {
    }

    public function evaluateRecordEventBestEffort(TenantContext $context, string $eventName, EntityRecordReference $record): void
    {
        try {
            if (! (bool) config('heos.enterprise.business_rules.enabled', true)) {
                return;
            }

            $this->ruleEngine->evaluate($context, \App\Modules\Sdk\Rules\Data\RuleEvaluationRequest::fromArray([
                'trigger_type' => $eventName,
                'facts' => $record->recordData->values ?? [],
                'metadata' => [
                    'module_key' => $record->moduleKey,
                    'entity_key' => $record->entityKey,
                    'subject_public_id' => $record->publicId,
                    'source' => 'entity',
                ],
            ]));
        } catch (\Throwable) {
        }
    }
}
