<?php

namespace App\Services\Enterprise\Workflow\Runtime;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Runtime\Contracts\WorkflowRuntimePort;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionContext;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionResult;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionStatistics;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowInstanceReference;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;

class WorkflowRuntimeService
{
    public function __construct(
        private readonly WorkflowRuntimePort $runtimePort,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
        private readonly WorkflowExecutionContextBuilder $contextBuilder,
        private readonly WorkflowRuntimeIntegrations $integrations,
    ) {
    }

    public function execute(
        TenantContext $context,
        string $definitionPublicId,
        ?array $inputPayload = null,
        ?WorkflowExecutionContext $executionContext = null,
    ): WorkflowExecutionResult {
        $this->runtimeBridge->requireCapability($context, 'workflow');
        $this->assertExecutePermission($context);

        $executionContext ??= $this->contextBuilder->fromTenant($context);

        $result = $this->runtimePort->execute(
            $this->scope($context),
            $definitionPublicId,
            $executionContext,
            $inputPayload,
            $context->user->id,
            $context->membership->id,
        );

        $this->integrate($context, $result->instance->publicId);

        return $result;
    }

    /**
     * @return list<WorkflowInstanceReference>
     */
    public function list(TenantContext $context, ?string $status = null): array
    {
        $this->runtimeBridge->requireCapability($context, 'workflow');
        $this->assertRuntimeReadPermission($context);

        return $this->runtimePort->listInstances($this->scope($context), $status);
    }

    public function show(TenantContext $context, string $publicId): WorkflowInstanceReference
    {
        $this->runtimeBridge->requireCapability($context, 'workflow');
        $this->assertRuntimeReadPermission($context);

        return $this->runtimePort->getInstance($this->scope($context), $publicId);
    }

    public function cancel(TenantContext $context, string $publicId): WorkflowInstanceReference
    {
        $this->runtimeBridge->requireCapability($context, 'workflow');
        $this->assertExecutePermission($context);

        $reference = $this->runtimePort->cancel(
            $this->scope($context),
            $publicId,
            $context->user->id,
            $context->membership->id,
        );

        $this->integrate($context, $reference->publicId);

        return $reference;
    }

    public function resume(TenantContext $context, string $publicId): WorkflowExecutionResult
    {
        $this->runtimeBridge->requireCapability($context, 'workflow');
        $this->assertExecutePermission($context);

        $result = $this->runtimePort->resume(
            $this->scope($context),
            $publicId,
            $context->user->id,
            $context->membership->id,
        );

        $this->integrate($context, $result->instance->publicId);

        return $result;
    }

    /**
     * @return array{steps: list<\App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionReference>, logs: list<array<string, mixed>>}
     */
    public function history(TenantContext $context, string $publicId): array
    {
        $this->runtimeBridge->requireCapability($context, 'workflow');
        $this->assertRuntimeReadPermission($context);

        return $this->runtimePort->history($this->scope($context), $publicId);
    }

    public function statistics(TenantContext $context): WorkflowExecutionStatistics
    {
        $this->runtimeBridge->requireCapability($context, 'workflow');
        $this->assertRuntimeReadPermission($context);

        return $this->runtimePort->statistics($this->scope($context));
    }

    private function integrate(TenantContext $context, string $instancePublicId): void
    {
        $instance = \App\Models\WorkflowInstance::query()
            ->with('definition')
            ->where('public_id', $instancePublicId)
            ->first();

        if ($instance === null) {
            return;
        }

        $this->integrations->indexInstanceBestEffort($context, $instance);

        $eventName = match ($instance->status->value) {
            'completed' => 'workflow.execution.completed',
            'failed' => 'workflow.execution.failed',
            'cancelled' => 'workflow.execution.cancelled',
            'waiting' => 'workflow.execution.waiting',
            default => 'workflow.execution.updated',
        };

        $this->integrations->dispatchExecutionEventBestEffort($context, $eventName, $instance);
    }

    private function scope(TenantContext $context): EnterpriseScope
    {
        return new EnterpriseScope(
            organizationPublicId: $context->organizationPublicId,
            workspacePublicId: $context->workspacePublicId,
        );
    }

    private function assertExecutePermission(TenantContext $context): void
    {
        if (! $this->allows($context, 'workflow.execute')) {
            abort(403, 'You are not allowed to execute workflows.');
        }
    }

    private function assertRuntimeReadPermission(TenantContext $context): void
    {
        if (! $this->allows($context, 'workflow.runtime.read')) {
            abort(403, 'You are not allowed to read workflow runtime data.');
        }
    }

    private function allows(TenantContext $context, string $permission): bool
    {
        return app(\App\Services\Authorization\TenantAuthorizationService::class)
            ->allows($context, $permission);
    }
}
