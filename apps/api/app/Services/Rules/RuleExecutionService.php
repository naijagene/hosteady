<?php

namespace App\Services\Rules;

use App\Models\RuleExecution;
use App\Modules\Sdk\Rules\Contracts\RuleExecutor;
use App\Modules\Sdk\Rules\Data\RuleContext;
use App\Modules\Sdk\Rules\Data\RuleDefinition;
use App\Modules\Sdk\Rules\Data\RuleExecutionRequest;
use App\Modules\Sdk\Rules\Data\RuleExecutionResult;
use App\Modules\Sdk\Rules\Enums\RuleExecutionStatus;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class RuleExecutionService implements RuleExecutor
{
    public function __construct(
        private readonly RuleEvaluationService $evaluationService,
        private readonly RuleActionExecutorService $actionExecutor,
        private readonly RuleAuditRecorder $auditRecorder,
    ) {
    }

    public function execute(RuleDefinition $rule, RuleContext $context): RuleExecutionResult
    {
        $evaluation = $this->evaluationService->evaluate($rule, $context);
        $execution = $this->actionExecutor->execute($rule->actions, $context->facts, $rule);

        return new RuleExecutionResult(
            publicId: (string) Str::uuid7(),
            status: RuleExecutionStatus::Completed->value,
            matchedRules: $evaluation->matched ? [$rule->publicId] : [],
            actionsApplied: $execution['actions_applied'] ?? [],
            warnings: $execution['warnings'] ?? [],
            violations: $execution['violations'] ?? [],
            metadata: [],
        );
    }

    public function executeRequest(TenantContext $tenantContext, RuleExecutionRequest $request): RuleExecutionResult
    {
        $evaluationRequest = new \App\Modules\Sdk\Rules\Data\RuleEvaluationRequest(
            context: $request->context,
            triggerType: $request->triggerType,
            rulePublicIds: $request->rulePublicIds,
            facts: $request->facts,
            metadata: $request->metadata,
        );

        $evaluation = $this->evaluationService->evaluateRequest($tenantContext, $evaluationRequest);
        $registry = app(RuleRegistryService::class);
        $actionsApplied = [];
        $warnings = [];
        $violations = $evaluation->violations;
        $matchedRules = [];

        foreach ($this->resolveRules($tenantContext, $request) as $rule) {
            if (! $this->conditionEvaluatorMatched($rule, $request->facts)) {
                continue;
            }

            $matchedRules[] = $rule->publicId;
            $execution = $this->actionExecutor->execute($rule->actions, $request->facts, $rule);
            $actionsApplied = array_merge($actionsApplied, $execution['actions_applied'] ?? []);
            $warnings = array_merge($warnings, $execution['warnings'] ?? []);
            $violations = array_merge($violations, $execution['violations'] ?? []);
        }

        $status = $warnings !== [] && $actionsApplied === [] ? RuleExecutionStatus::Partial->value : RuleExecutionStatus::Completed->value;

        $model = RuleExecution::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $tenantContext->organization->id,
            'workspace_id' => $tenantContext->workspace?->id,
            'trigger_type' => $request->triggerType,
            'status' => $status,
            'matched_rules_json' => $matchedRules,
            'actions_applied_json' => $actionsApplied,
            'warnings_json' => $warnings,
            'violations_json' => $violations,
            'facts_json' => $request->facts,
            'metadata' => $request->metadata,
            'actor_membership_id' => $tenantContext->membership->id,
        ]);

        $result = new RuleExecutionResult(
            publicId: $model->public_id,
            status: $status,
            matchedRules: $matchedRules,
            actionsApplied: $actionsApplied,
            warnings: $warnings,
            violations: $violations,
            metadata: $request->metadata,
        );

        $this->auditRecorder->recordExecuted($result, $tenantContext);

        return $result;
    }

    private function conditionEvaluatorMatched(RuleDefinition $rule, array $facts): bool
    {
        return app(RuleConditionEvaluatorService::class)->evaluateAll($rule->conditions, $facts);
    }

    /** @return list<RuleDefinition> */
    private function resolveRules(TenantContext $tenantContext, RuleExecutionRequest $request): array
    {
        $registry = app(RuleRegistryService::class);

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
            $request->metadata['module_key'] ?? null,
            $request->metadata['entity_key'] ?? null,
        );
    }
}
