<?php

namespace App\Services\Rules;

use App\Models\FormSubmission;
use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Modules\Sdk\Form\Data\FormSubmissionRequest;
use App\Modules\Sdk\Form\Data\FormValidationIssue;
use App\Modules\Sdk\Form\Enums\FormValidationSeverity;
use App\Support\Tenant\TenantContext;

class RuleFormBridge
{
    public function __construct(
        private readonly EnterpriseRuleEngineService $ruleEngine,
    ) {
    }

    /**
     * @param  list<FormValidationIssue>  $issues
     */
    public function validateFormBestEffort(FormSubmissionRequest $request, FormDefinition $definition, array &$issues): void
    {
        if (! app()->bound(TenantContext::class) || ! (bool) config('heos.enterprise.business_rules.enabled', true)) {
            return;
        }

        try {
            $context = app(TenantContext::class);
            $result = $this->ruleEngine->evaluate($context, \App\Modules\Sdk\Rules\Data\RuleEvaluationRequest::fromArray([
                'trigger_type' => 'form_validating',
                'facts' => $request->values,
                'metadata' => [
                    'module_key' => $definition->moduleKey,
                    'form_key' => $definition->formKey,
                    'source' => 'form',
                ],
            ]));

            foreach ($result->violations as $violation) {
                $severity = FormValidationSeverity::Warning->value;
                if (($violation['severity'] ?? '') === 'error' || ($violation['severity'] ?? '') === 'critical') {
                    $severity = FormValidationSeverity::Error->value;
                }

                $issues[] = new FormValidationIssue(
                    code: $violation['code'] ?? 'rule_violation',
                    message: $violation['message'] ?? 'Business rule violation.',
                    severity: $severity,
                    field: $violation['field'] ?? null,
                );
            }
        } catch (\Throwable) {
        }
    }

    public function dispatchSubmittedBestEffort(FormSubmissionRequest $request, FormDefinition $definition, FormSubmission $submission): void
    {
        if (! app()->bound(TenantContext::class) || ! (bool) config('heos.enterprise.business_rules.enabled', true)) {
            return;
        }

        try {
            $context = app(TenantContext::class);
            $this->ruleEngine->execute($context, \App\Modules\Sdk\Rules\Data\RuleExecutionRequest::fromArray([
                'trigger_type' => 'form_submitted',
                'facts' => $request->values,
                'metadata' => [
                    'module_key' => $definition->moduleKey,
                    'form_key' => $definition->formKey,
                    'submission_public_id' => $submission->public_id,
                    'source' => 'form',
                ],
            ]));
        } catch (\Throwable) {
        }
    }
}
