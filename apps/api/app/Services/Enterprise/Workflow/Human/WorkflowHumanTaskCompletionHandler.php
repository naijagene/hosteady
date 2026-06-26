<?php

namespace App\Services\Enterprise\Workflow\Human;

use App\Models\WorkflowInstance;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Human\Contracts\ApprovalDecisionHandler;
use App\Modules\Sdk\Workflow\Human\Data\ApprovalDecision;
use App\Modules\Sdk\Workflow\Human\Data\HumanTaskReference;
use App\Modules\Sdk\Workflow\Runtime\Contracts\WorkflowRuntimePort;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionContext;
use App\Modules\Sdk\Workflow\Runtime\Enums\WorkflowInstanceStatus;

class WorkflowHumanTaskCompletionHandler implements ApprovalDecisionHandler
{
    public function __construct(
        private readonly WorkflowRuntimePort $runtimePort,
    ) {
    }

    public function afterApproved(HumanTaskReference $task, ApprovalDecision $decision): void
    {
        $this->resumeIfWaiting($task);
    }

    public function afterRejected(HumanTaskReference $task, ApprovalDecision $decision): void
    {
        $this->resumeIfWaiting($task);
    }

    public function afterCompleted(HumanTaskReference $task): void
    {
        $this->resumeIfWaiting($task);
    }

    private function resumeIfWaiting(HumanTaskReference $task): void
    {
        $instancePublicId = $task->workflowInstancePublicId;

        if ($instancePublicId === null) {
            return;
        }

        $instance = WorkflowInstance::query()
            ->with(['organization', 'workspace'])
            ->where('public_id', $instancePublicId)
            ->first();

        if ($instance === null || $instance->status !== WorkflowInstanceStatus::Waiting) {
            return;
        }

        $scope = new EnterpriseScope(
            organizationPublicId: $instance->organization->public_id,
            workspacePublicId: $instance->workspace?->public_id,
        );

        $context = new WorkflowExecutionContext(
            organizationPublicId: $scope->organizationPublicId,
            workspacePublicId: $scope->workspacePublicId,
            metadata: ['workflow_instance_id' => $instance->public_id],
        );

        try {
            $this->runtimePort->resume($scope, $instancePublicId);
        } catch (\Throwable) {
        }
    }
}
