<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkflowAutomationRuleResource;
use App\Http\Resources\WorkflowTimerResource;
use App\Http\Resources\WorkflowTriggerResource;
use App\Models\WorkflowAutomationRule;
use App\Services\Enterprise\Workflow\Automation\WorkflowAutomationService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class WorkflowAutomationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly WorkflowAutomationService $automationService,
    ) {
    }

    public function indexRules(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkflowAutomationRule::class);

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:active,disabled'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return WorkflowAutomationRuleResource::collection(
            $this->automationService->listRules($context, $validated['status'] ?? null),
        );
    }

    public function storeRule(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('manage', WorkflowAutomationRule::class);

        $validated = $request->validate([
            'workflow_definition_public_id' => ['required', 'uuid'],
            'trigger_type' => ['required', 'string', 'in:manual,platform_event,entity_created,entity_updated,schedule,api'],
            'trigger_config' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return (new WorkflowAutomationRuleResource(
            $this->automationService->createRule($context, $validated),
        ))->response()->setStatusCode(201);
    }

    public function enableRule(string $publicId): WorkflowAutomationRuleResource
    {
        $this->authorize('manage', WorkflowAutomationRule::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new WorkflowAutomationRuleResource(
            $this->automationService->enableRule($context, $publicId),
        );
    }

    public function disableRule(string $publicId): WorkflowAutomationRuleResource
    {
        $this->authorize('manage', WorkflowAutomationRule::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new WorkflowAutomationRuleResource(
            $this->automationService->disableRule($context, $publicId),
        );
    }

    public function destroyRule(string $publicId): \Illuminate\Http\Response
    {
        $this->authorize('manage', WorkflowAutomationRule::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $this->automationService->deleteRule($context, $publicId);

        return response()->noContent();
    }

    public function indexTriggers(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkflowAutomationRule::class);

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return WorkflowTriggerResource::collection(
            $this->automationService->listTriggers($context, $validated['limit'] ?? 50),
        );
    }

    public function indexTimers(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkflowAutomationRule::class);

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:active,executed,failed,cancelled'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return WorkflowTimerResource::collection(
            $this->automationService->listTimers(
                $context,
                $validated['status'] ?? null,
                $validated['limit'] ?? 50,
            ),
        );
    }
}
