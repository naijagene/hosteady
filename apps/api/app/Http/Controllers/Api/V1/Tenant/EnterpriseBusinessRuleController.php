<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\RuleDefinitionResource;
use App\Http\Resources\RuleEvaluationResultResource;
use App\Http\Resources\RuleExecutionResultResource;
use App\Http\Resources\RuleHealthResource;
use App\Http\Resources\RuleSetResource;
use App\Http\Resources\RuleStatisticsResource;
use App\Models\RuleDefinition;
use App\Modules\Sdk\Rules\Data\RuleDefinition as RuleDefinitionDto;
use App\Modules\Sdk\Rules\Data\RuleEvaluationRequest;
use App\Modules\Sdk\Rules\Data\RuleExecutionRequest;
use App\Modules\Sdk\Rules\Data\RuleSetDefinition;
use App\Services\Rules\RuleDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnterpriseBusinessRuleController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly RuleDevelopmentService $developmentService,
    ) {
    }

    public function indexSets(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', RuleDefinition::class);
        $context = app(TenantContext::class);

        return RuleSetResource::collection($this->developmentService->listSets($context, (int) ($request->integer('limit') ?: 50)));
    }

    public function storeSet(Request $request): JsonResponse
    {
        $this->authorize('create', RuleDefinition::class);
        $context = app(TenantContext::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scope' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'module_key' => ['nullable', 'string', 'max:64'],
            'metadata' => ['nullable', 'array'],
        ]);

        $created = $this->developmentService->createSet($context, RuleSetDefinition::fromArray($validated));

        return (new RuleSetResource($created))->response()->setStatusCode(201);
    }

    public function enableSet(string $ruleSetPublicId): RuleSetResource
    {
        $this->authorize('update', RuleDefinition::class);

        return new RuleSetResource($this->developmentService->enableSet(app(TenantContext::class), $ruleSetPublicId));
    }

    public function disableSet(string $ruleSetPublicId): RuleSetResource
    {
        $this->authorize('update', RuleDefinition::class);

        return new RuleSetResource($this->developmentService->disableSet(app(TenantContext::class), $ruleSetPublicId));
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', RuleDefinition::class);

        return RuleDefinitionResource::collection($this->developmentService->listRules(app(TenantContext::class), (int) ($request->integer('limit') ?: 50)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', RuleDefinition::class);
        $validated = $request->validate([
            'rule_set_public_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
            'scope' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'trigger_type' => ['nullable', 'string'],
            'priority' => ['nullable', 'integer'],
            'conditions' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
            'module_key' => ['nullable', 'string'],
            'entity_key' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        $created = $this->developmentService->createRule(app(TenantContext::class), RuleDefinitionDto::fromArray($validated));

        return (new RuleDefinitionResource($created))->response()->setStatusCode(201);
    }

    public function show(string $rulePublicId): RuleDefinitionResource
    {
        $this->authorize('view', RuleDefinition::class);

        return new RuleDefinitionResource($this->developmentService->showRule(app(TenantContext::class), $rulePublicId));
    }

    public function update(Request $request, string $rulePublicId): RuleDefinitionResource
    {
        $this->authorize('update', RuleDefinition::class);
        $validated = $request->validate([
            'rule_set_public_id' => ['nullable', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
            'scope' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'trigger_type' => ['nullable', 'string'],
            'priority' => ['nullable', 'integer'],
            'conditions' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
            'module_key' => ['nullable', 'string'],
            'entity_key' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);
        $validated['public_id'] = $rulePublicId;

        return new RuleDefinitionResource($this->developmentService->updateRule(app(TenantContext::class), RuleDefinitionDto::fromArray($validated)));
    }

    public function enable(string $rulePublicId): RuleDefinitionResource
    {
        $this->authorize('update', RuleDefinition::class);

        return new RuleDefinitionResource($this->developmentService->enableRule(app(TenantContext::class), $rulePublicId));
    }

    public function disable(string $rulePublicId): RuleDefinitionResource
    {
        $this->authorize('update', RuleDefinition::class);

        return new RuleDefinitionResource($this->developmentService->disableRule(app(TenantContext::class), $rulePublicId));
    }

    public function evaluate(Request $request): RuleEvaluationResultResource
    {
        $this->authorize('evaluate', RuleDefinition::class);
        $validated = $request->validate([
            'trigger_type' => ['nullable', 'string'],
            'rule_public_ids' => ['nullable', 'array'],
            'facts' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        return new RuleEvaluationResultResource($this->developmentService->evaluate(app(TenantContext::class), RuleEvaluationRequest::fromArray($validated)));
    }

    public function execute(Request $request): RuleExecutionResultResource
    {
        $this->authorize('execute', RuleDefinition::class);
        $validated = $request->validate([
            'trigger_type' => ['nullable', 'string'],
            'rule_public_ids' => ['nullable', 'array'],
            'facts' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        return new RuleExecutionResultResource($this->developmentService->execute(app(TenantContext::class), RuleExecutionRequest::fromArray($validated)));
    }

    public function evaluations(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', RuleDefinition::class);

        return RuleEvaluationResultResource::collection($this->developmentService->listEvaluations(app(TenantContext::class), (int) ($request->integer('limit') ?: 50)));
    }

    public function executions(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', RuleDefinition::class);

        return RuleExecutionResultResource::collection($this->developmentService->listExecutions(app(TenantContext::class), (int) ($request->integer('limit') ?: 50)));
    }

    public function health(): RuleHealthResource
    {
        $this->authorize('viewAny', RuleDefinition::class);

        return new RuleHealthResource($this->developmentService->health(app(TenantContext::class)));
    }

    public function statistics(): RuleStatisticsResource
    {
        $this->authorize('viewAny', RuleDefinition::class);

        return new RuleStatisticsResource($this->developmentService->statistics(app(TenantContext::class)));
    }
}
