<?php

namespace App\Services\Rules;

use App\Modules\Sdk\DataRepository\Data\EntityRecord;
use App\Modules\Sdk\DataRepository\Exceptions\EntityRecordMutationException;
use App\Modules\Sdk\Rules\Data\RuleEvaluationRequest;
use App\Modules\Sdk\Rules\Enums\RuleSeverity;
use App\Support\Tenant\TenantContext;

class RuleDataRepositoryBridge
{
    public function __construct(
        private readonly EnterpriseRuleEngineService $ruleEngine,
    ) {
    }

    public function assertAllowedBeforeMutation(
        TenantContext $context,
        string $triggerType,
        string $moduleKey,
        string $entityKey,
        ?string $recordPublicId,
        array $values,
    ): void {
        if (! (bool) config('heos.enterprise.business_rules.enabled', true)) {
            return;
        }

        $result = $this->ruleEngine->evaluate($context, RuleEvaluationRequest::fromArray([
            'trigger_type' => $triggerType,
            'facts' => $values,
            'metadata' => [
                'module_key' => $moduleKey,
                'entity_key' => $entityKey,
                'subject_public_id' => $recordPublicId,
                'source' => 'data_repository',
            ],
        ]));

        if (! $result->allowed) {
            throw new EntityRecordMutationException('Entity mutation blocked by business rules.');
        }
    }

    public function dispatchAfterMutationBestEffort(
        TenantContext $context,
        string $triggerType,
        EntityRecord $record,
    ): void {
        try {
            if (! (bool) config('heos.enterprise.business_rules.enabled', true)) {
                return;
            }

            $this->ruleEngine->execute($context, \App\Modules\Sdk\Rules\Data\RuleExecutionRequest::fromArray([
                'trigger_type' => $triggerType,
                'facts' => $record->recordData->values ?? [],
                'metadata' => [
                    'module_key' => $record->moduleKey,
                    'entity_key' => $record->entityKey,
                    'subject_public_id' => $record->publicId,
                    'source' => 'data_repository',
                ],
            ]));
        } catch (\Throwable) {
        }
    }
}
