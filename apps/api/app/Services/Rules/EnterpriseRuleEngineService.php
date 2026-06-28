<?php

namespace App\Services\Rules;

use App\Modules\Sdk\Rules\Contracts\RuleEngine;
use App\Modules\Sdk\Rules\Data\RuleEvaluationRequest;
use App\Modules\Sdk\Rules\Data\RuleEvaluationResult;
use App\Modules\Sdk\Rules\Data\RuleExecutionRequest;
use App\Modules\Sdk\Rules\Data\RuleExecutionResult;
use App\Support\Tenant\TenantContext;

class EnterpriseRuleEngineService implements RuleEngine
{
    public function __construct(
        private readonly RuleEvaluationService $evaluationService,
        private readonly RuleExecutionService $executionService,
    ) {
    }

    public function evaluate(TenantContext $context, RuleEvaluationRequest $request): RuleEvaluationResult
    {
        return $this->evaluationService->evaluateRequest($context, $request);
    }

    public function execute(TenantContext $context, RuleExecutionRequest $request): RuleExecutionResult
    {
        return $this->executionService->executeRequest($context, $request);
    }
}
