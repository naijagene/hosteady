<?php

namespace App\Services\Rules;

use App\Models\RuleEvaluation;
use App\Models\RuleExecution;
use App\Modules\Sdk\Rules\Contracts\RuleEngine;
use App\Modules\Sdk\Rules\Data\RuleDefinition;
use App\Modules\Sdk\Rules\Data\RuleEvaluationRequest;
use App\Modules\Sdk\Rules\Data\RuleEvaluationResult;
use App\Modules\Sdk\Rules\Data\RuleExecutionRequest;
use App\Modules\Sdk\Rules\Data\RuleExecutionResult;
use App\Modules\Sdk\Rules\Data\RuleHealthReport;
use App\Modules\Sdk\Rules\Data\RuleSetDefinition;
use App\Modules\Sdk\Rules\Data\RuleStatistics;
use App\Modules\Sdk\Rules\Exceptions\RuleNotFoundException;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RuleDevelopmentService
{
    public function __construct(
        private readonly RuleEngine $ruleEngine,
        private readonly RuleSetService $ruleSetService,
        private readonly RuleDefinitionService $ruleDefinitionService,
        private readonly RuleHealthService $healthService,
        private readonly RuleStatisticsService $statisticsService,
        private readonly RulePermissionService $permissionService,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
    ) {
    }

    /** @return list<RuleSetDefinition> */
    public function listSets(TenantContext $context, int $limit = 50): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->ruleSetService->list($context->organization->id, $context->workspace?->id, $limit);
    }

    public function createSet(TenantContext $context, RuleSetDefinition $definition): RuleSetDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->ruleSetService->create($context->organization->id, $context->workspace?->id, $definition);
    }

    public function enableSet(TenantContext $context, string $publicId): RuleSetDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->ruleSetService->enable($context->organization->id, $context->workspace?->id, $publicId);
    }

    public function disableSet(TenantContext $context, string $publicId): RuleSetDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->ruleSetService->disable($context->organization->id, $context->workspace?->id, $publicId);
    }

    /** @return list<RuleDefinition> */
    public function listRules(TenantContext $context, int $limit = 50): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->ruleDefinitionService->list($context->organization->id, $context->workspace?->id, $limit);
    }

    public function showRule(TenantContext $context, string $publicId): RuleDefinition
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $rule = $this->ruleDefinitionService->find($context->organization->id, $context->workspace?->id, $publicId);

        if ($rule === null) {
            throw new RuleNotFoundException(sprintf('Rule definition [%s] was not found.', $publicId));
        }

        return $rule;
    }

    public function createRule(TenantContext $context, RuleDefinition $definition): RuleDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->ruleDefinitionService->create($context->organization->id, $context->workspace?->id, $definition);
    }

    public function updateRule(TenantContext $context, RuleDefinition $definition): RuleDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->ruleDefinitionService->update($context->organization->id, $context->workspace?->id, $definition);
    }

    public function enableRule(TenantContext $context, string $publicId): RuleDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->ruleDefinitionService->enable($context->organization->id, $context->workspace?->id, $publicId);
    }

    public function disableRule(TenantContext $context, string $publicId): RuleDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->ruleDefinitionService->disable($context->organization->id, $context->workspace?->id, $publicId);
    }

    public function evaluate(TenantContext $context, RuleEvaluationRequest $request): RuleEvaluationResult
    {
        $this->requireCapability($context);
        $this->assertEvaluate($context);

        return $this->ruleEngine->evaluate($context, $request);
    }

    public function execute(TenantContext $context, RuleExecutionRequest $request): RuleExecutionResult
    {
        $this->requireCapability($context);
        $this->assertExecute($context);

        return $this->ruleEngine->execute($context, $request);
    }

    /** @return list<RuleEvaluationResult> */
    public function listEvaluations(TenantContext $context, int $limit = 50): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return RuleEvaluation::query()
            ->where('organization_id', $context->organization->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (RuleEvaluation $model) => RuleMapper::toEvaluationResult($model))
            ->all();
    }

    /** @return list<RuleExecutionResult> */
    public function listExecutions(TenantContext $context, int $limit = 50): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return RuleExecution::query()
            ->where('organization_id', $context->organization->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (RuleExecution $model) => RuleMapper::toExecutionResult($model))
            ->all();
    }

    public function health(TenantContext $context): RuleHealthReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->healthService->health($context);
    }

    public function statistics(TenantContext $context): RuleStatistics
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->statisticsService->statisticsForScope($context->organization, $context->workspace);
    }

    private function requireCapability(TenantContext $context): void
    {
        $this->runtimeBridge->requireCapability($context, 'business_rules');
    }

    private function assertRead(TenantContext $context): void
    {
        if (! $this->permissionService->canRead($context)) {
            throw new HttpException(403, 'You do not have permission to read business rules.');
        }
    }

    private function assertManage(TenantContext $context): void
    {
        if (! $this->permissionService->canManage($context)) {
            throw new HttpException(403, 'You do not have permission to manage business rules.');
        }
    }

    private function assertEvaluate(TenantContext $context): void
    {
        if (! $this->permissionService->canEvaluate($context)) {
            throw new HttpException(403, 'You do not have permission to evaluate business rules.');
        }
    }

    private function assertExecute(TenantContext $context): void
    {
        if (! $this->permissionService->canExecute($context)) {
            throw new HttpException(403, 'You do not have permission to execute business rules.');
        }
    }
}
