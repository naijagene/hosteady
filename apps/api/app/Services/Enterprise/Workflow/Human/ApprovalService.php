<?php

namespace App\Services\Enterprise\Workflow\Human;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Human\Contracts\ApprovalPort;
use App\Modules\Sdk\Workflow\Human\Contracts\HumanTaskPort;
use App\Modules\Sdk\Workflow\Human\Data\ApprovalDecision;
use App\Modules\Sdk\Workflow\Human\Data\ApprovalReference;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;

class ApprovalService
{
    public function __construct(
        private readonly ApprovalPort $approvalPort,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
        private readonly HumanTaskIntegrations $integrations,
        private readonly WorkflowHumanTaskCompletionHandler $completionHandler,
        private readonly HumanTaskPort $humanTaskPort,
    ) {
    }

    /**
     * @return list<ApprovalReference>
     */
    public function list(TenantContext $context, ?string $status = null): array
    {
        $this->runtimeBridge->requireCapability($context, 'approvals');
        $this->assertReadPermission($context);

        return $this->approvalPort->list($this->scope($context), $status);
    }

    public function show(TenantContext $context, string $publicId): ApprovalReference
    {
        $this->runtimeBridge->requireCapability($context, 'approvals');
        $this->assertReadPermission($context);

        return $this->approvalPort->get($this->scope($context), $publicId);
    }

    public function approve(TenantContext $context, string $publicId, ?string $comment = null): ApprovalDecision
    {
        $this->runtimeBridge->requireCapability($context, 'approvals');
        $this->assertDecidePermission($context);

        $decision = $this->approvalPort->approve(
            $this->scope($context),
            $publicId,
            $comment,
            $context->user->id,
            $context->membership->id,
        );

        $task = $this->humanTaskPort->get($this->scope($context), $publicId);
        $this->completionHandler->afterApproved($task, $decision);
        $this->integrate($context, $publicId);

        return $decision;
    }

    public function reject(TenantContext $context, string $publicId, ?string $comment = null): ApprovalDecision
    {
        $this->runtimeBridge->requireCapability($context, 'approvals');
        $this->assertDecidePermission($context);

        $decision = $this->approvalPort->reject(
            $this->scope($context),
            $publicId,
            $comment,
            $context->user->id,
            $context->membership->id,
        );

        $task = $this->humanTaskPort->get($this->scope($context), $publicId);
        $this->completionHandler->afterRejected($task, $decision);
        $this->integrate($context, $publicId);

        return $decision;
    }

    private function integrate(TenantContext $context, string $taskPublicId): void
    {
        $task = \App\Models\WorkflowHumanTask::query()
            ->with('workflowInstance.definition')
            ->where('public_id', $taskPublicId)
            ->first();

        if ($task === null) {
            return;
        }

        $this->integrations->indexTaskBestEffort($context, $task);
        $this->integrations->notifyTaskEventBestEffort($context, 'approval.updated', $task);
    }

    private function scope(TenantContext $context): EnterpriseScope
    {
        return new EnterpriseScope(
            organizationPublicId: $context->organizationPublicId,
            workspacePublicId: $context->workspacePublicId,
        );
    }

    private function assertReadPermission(TenantContext $context): void
    {
        if (! $this->allows($context, 'approval.read')) {
            abort(403, 'You are not allowed to read approvals.');
        }
    }

    private function assertDecidePermission(TenantContext $context): void
    {
        if (! $this->allows($context, 'approval.decide')) {
            abort(403, 'You are not allowed to decide approvals.');
        }
    }

    private function allows(TenantContext $context, string $permission): bool
    {
        return app(\App\Services\Authorization\TenantAuthorizationService::class)
            ->allows($context, $permission);
    }
}
