<?php

namespace App\Modules\Sdk\Rules\Contracts;

interface RuleEngine
{
    public function evaluate(\App\Support\Tenant\TenantContext $context, \App\Modules\Sdk\Rules\Data\RuleEvaluationRequest $request): \App\Modules\Sdk\Rules\Data\RuleEvaluationResult;

    public function execute(\App\Support\Tenant\TenantContext $context, \App\Modules\Sdk\Rules\Data\RuleExecutionRequest $request): \App\Modules\Sdk\Rules\Data\RuleExecutionResult;
}
