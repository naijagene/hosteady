<?php

namespace App\Services\Rules;

use App\Models\RuleEvaluation;
use App\Modules\Sdk\Rules\Contracts\RuleConditionEvaluator;
use App\Modules\Sdk\Rules\Contracts\RuleEvaluator;
use App\Modules\Sdk\Rules\Data\RuleContext;
use App\Modules\Sdk\Rules\Data\RuleDefinition;
use App\Modules\Sdk\Rules\Data\RuleEvaluationRequest;
use App\Modules\Sdk\Rules\Data\RuleEvaluationResult;
use App\Modules\Sdk\Rules\Data\RuleViolation;
use App\Modules\Sdk\Rules\Enums\RuleSeverity;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class RuleEvaluationService implements RuleEvaluator
{
    public function __construct(
        private readonly RuleConditionEvaluator $conditionEvaluator,
        private readonly RuleActionExecutorService $actionExecutor,
        private readonly RuleTraceService $traceService,
        private readonly RuleAuditRecorder $auditRecorder,
    ) {
    }

    public function evaluate(RuleDefinition $rule, RuleContext $context): RuleEvaluationResult
    {
        return $this->evaluateSingle($rule, $context->facts);
    }

    public function evaluateRequest(TenantContext $tenantContext, RuleEvaluationRequest $request): RuleEvaluationResult
    {
        $rules = $this->resolveRules($tenantContext, $request);
        $facts = $request->facts;
        $violations = [];
        $traces = [];
        $matched = false;
        $allowed = true;

        foreach ($rules as $rule) {
            $started = (int) round(microtime(true) * 1000);
            $ruleMatched = $this->conditionEvaluator->evaluateAll($rule->conditions, $facts);
            $conditionResults = ['matched' => $ruleMatched];
            $actionResults = [];

            if ($ruleMatched) {
                $matched = true;
                $execution = $this->actionExecutor->execute($rule->actions, $facts, $rule);
                $actionResults = $execution['actions_applied'] ?? [];

                foreach ($execution['violations'] ?? [] as $violation) {
                    $violations[] = $violation;
                    $severity = RuleSeverity::tryFrom($violation['severity'] ?? 'warning') ?? RuleSeverity::Warning;
                    if (in_array($severity, [RuleSeverity::Error, RuleSeverity::Critical], true)) {
                        $allowed = false;
                    }
                }
            }

            $traces[] = $this->traceService->trace(
                $rule,
                $ruleMatched,
                $conditionResults,
                $actionResults,
                max(0, (int) round(microtime(true) * 1000) - $started),
            )->toArray();
        }

        $model = RuleEvaluation::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $tenantContext->organization->id,
            'workspace_id' => $tenantContext->workspace?->id,
            'trigger_type' => $request->triggerType,
            'matched' => $matched,
            'allowed' => $allowed,
            'violations_json' => $violations,
            'traces_json' => $traces,
            'facts_json' => $facts,
            'metadata' => $request->metadata,
            'actor_membership_id' => $tenantContext->membership->id,
        ]);

        $result = new RuleEvaluationResult(
            publicId: $model->public_id,
            matched: $matched,
            allowed: $allowed,
            violations: $violations,
            traces: $traces,
            metadata: $request->metadata,
        );

        $this->auditRecorder->recordEvaluated($result, $tenantContext);

        return $result;
    }

    private function evaluateSingle(RuleDefinition $rule, array $facts): RuleEvaluationResult
    {
        $matched = $this->conditionEvaluator->evaluateAll($rule->conditions, $facts);
        $violations = [];
        $allowed = true;

        if ($matched) {
            $execution = $this->actionExecutor->execute($rule->actions, $facts, $rule);
            $violations = $execution['violations'] ?? [];
            foreach ($violations as $violation) {
                $severity = RuleSeverity::tryFrom($violation['severity'] ?? 'warning') ?? RuleSeverity::Warning;
                if (in_array($severity, [RuleSeverity::Error, RuleSeverity::Critical], true)) {
                    $allowed = false;
                }
            }
        }

        return new RuleEvaluationResult(
            publicId: (string) Str::uuid7(),
            matched: $matched,
            allowed: $allowed,
            violations: $violations,
            traces: [],
            metadata: [],
        );
    }

    /** @return list<RuleDefinition> */
    private function resolveRules(TenantContext $tenantContext, RuleEvaluationRequest $request): array
    {
        $registry = app(RuleRegistryService::class);
        $metadata = $request->metadata;

        if ($request->rulePublicIds !== []) {
            $rules = [];
            foreach ($request->rulePublicIds as $publicId) {
                $rule = $registry->find($tenantContext->organization->id, $tenantContext->workspace?->id, $publicId);
                if ($rule !== null) {
                    $rules[] = $rule;
                }
            }

            return $rules;
        }

        return $registry->listEnabled(
            $tenantContext->organization->id,
            $tenantContext->workspace?->id,
            $request->triggerType,
            $metadata['module_key'] ?? null,
            $metadata['entity_key'] ?? null,
        );
    }
}
