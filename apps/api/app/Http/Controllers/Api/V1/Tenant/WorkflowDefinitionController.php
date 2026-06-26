<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkflowExecutionResultResource;
use App\Http\Resources\WorkflowDefinitionResource;
use App\Http\Resources\WorkflowValidationReportResource;
use App\Http\Resources\WorkflowVersionResource;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Modules\Sdk\Workflow\Data\WorkflowDefinitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowNodeData;
use App\Modules\Sdk\Workflow\Data\WorkflowTransitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowTriggerData;
use App\Modules\Sdk\Workflow\Data\WorkflowVariableData;
use App\Services\Enterprise\Workflow\Runtime\WorkflowRuntimeService;
use App\Services\Enterprise\Workflow\WorkflowDefinitionService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class WorkflowDefinitionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly WorkflowDefinitionService $workflowDefinitionService,
        private readonly WorkflowRuntimeService $workflowRuntimeService,
    ) {
    }

    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkflowDefinition::class);

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return WorkflowDefinitionResource::collection(
            $this->workflowDefinitionService->list($context, $validated['status'] ?? null),
        );
    }

    public function show(string $publicId): WorkflowDefinitionResource
    {
        $this->authorize('view', WorkflowDefinition::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new WorkflowDefinitionResource(
            $this->workflowDefinitionService->show($context, $publicId),
        );
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', WorkflowDefinition::class);

        $validated = $this->validatedDefinitionPayload($request);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return (new WorkflowDefinitionResource(
            $this->workflowDefinitionService->create($context, $this->buildDefinitionData($validated)),
        ))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $publicId): WorkflowDefinitionResource
    {
        $this->authorize('update', WorkflowDefinition::class);

        $validated = $this->validatedDefinitionPayload($request, partial: true);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);
        $existing = $this->workflowDefinitionService->show($context, $publicId);

        return new WorkflowDefinitionResource(
            $this->workflowDefinitionService->update($context, $publicId, $this->buildDefinitionData(array_merge([
                'workflow_key' => $existing->workflowKey,
                'name' => $existing->name,
                'description' => $existing->description,
                'module_key' => $existing->moduleKey,
                'category_public_id' => $existing->categoryPublicId,
                'metadata' => $existing->metadata,
            ], $validated))),
        );
    }

    public function publish(Request $request, string $publicId): \Illuminate\Http\JsonResponse
    {
        $this->authorize('publish', WorkflowDefinition::class);

        $validated = $request->validate([
            'version_public_id' => ['nullable', 'string', 'max:128'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $result = $this->workflowDefinitionService->publish(
            $context,
            $publicId,
            $validated['version_public_id'] ?? null,
        );

        return response()->json([
            'data' => [
                'definition' => $result->definition->toArray(),
                'published_version' => $result->publishedVersion->toArray(),
                'validation_report' => $result->validationReport->toArray(),
            ],
        ]);
    }

    public function execute(Request $request, string $publicId): WorkflowExecutionResultResource
    {
        $this->authorize('execute', WorkflowInstance::class);

        $validated = $request->validate([
            'input' => ['nullable', 'array'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new WorkflowExecutionResultResource(
            $this->workflowRuntimeService->execute(
                $context,
                $publicId,
                $validated['input'] ?? null,
            ),
        );
    }

    public function archive(string $publicId): WorkflowDefinitionResource
    {
        $this->authorize('archive', WorkflowDefinition::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new WorkflowDefinitionResource(
            $this->workflowDefinitionService->archive($context, $publicId),
        );
    }

    public function versions(string $publicId): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('view', WorkflowDefinition::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return WorkflowVersionResource::collection(
            $this->workflowDefinitionService->listVersions($context, $publicId),
        );
    }

    public function validateDefinition(Request $request): WorkflowValidationReportResource
    {
        $this->authorize('viewAny', WorkflowDefinition::class);

        $validated = $this->validatedDefinitionPayload($request);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new WorkflowValidationReportResource(
            $this->workflowDefinitionService->validate($context, $this->buildDefinitionData($validated)),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedDefinitionPayload(Request $request, bool $partial = false): array
    {
        $rules = [
            'workflow_key' => [$partial ? 'sometimes' : 'required', 'string', 'max:128'],
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'module_key' => ['nullable', 'string', 'max:64'],
            'category_public_id' => ['nullable', 'string', 'max:128'],
            'metadata' => ['nullable', 'array'],
            'nodes' => ['nullable', 'array'],
            'transitions' => ['nullable', 'array'],
            'triggers' => ['nullable', 'array'],
            'variables' => ['nullable', 'array'],
        ];

        return $request->validate($rules);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function buildDefinitionData(array $validated): WorkflowDefinitionData
    {
        $nodes = array_map(
            fn (array $node) => WorkflowNodeData::fromArray($node),
            is_array($validated['nodes'] ?? null) ? $validated['nodes'] : [],
        );
        $transitions = array_map(
            fn (array $transition) => WorkflowTransitionData::fromArray($transition),
            is_array($validated['transitions'] ?? null) ? $validated['transitions'] : [],
        );
        $triggers = array_map(
            fn (array $trigger) => WorkflowTriggerData::fromArray($trigger),
            is_array($validated['triggers'] ?? null) ? $validated['triggers'] : [],
        );
        $variables = array_map(
            fn (array $variable) => WorkflowVariableData::fromArray($variable),
            is_array($validated['variables'] ?? null) ? $validated['variables'] : [],
        );

        return new WorkflowDefinitionData(
            workflowKey: (string) $validated['workflow_key'],
            name: (string) $validated['name'],
            description: $validated['description'] ?? null,
            moduleKey: $validated['module_key'] ?? null,
            categoryPublicId: $validated['category_public_id'] ?? null,
            nodes: $nodes,
            transitions: $transitions,
            triggers: $triggers,
            variables: $variables,
            metadata: $validated['metadata'] ?? [],
        );
    }
}
