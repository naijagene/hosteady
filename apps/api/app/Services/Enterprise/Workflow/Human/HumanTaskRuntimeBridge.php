<?php

namespace App\Services\Enterprise\Workflow\Human;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Enums\WorkflowNodeType;
use App\Modules\Sdk\Workflow\Human\Contracts\HumanTaskPort;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowActionResult;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionContext;
use App\Modules\Sdk\Workflow\Runtime\Enums\WorkflowActionStatus;

class HumanTaskRuntimeBridge
{
    public function __construct(
        private readonly HumanTaskPort $humanTaskPort,
    ) {
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $variables
     */
    public function handle(
        string $nodeType,
        array $node,
        WorkflowExecutionContext $context,
        array $variables,
    ): WorkflowActionResult {
        $instancePublicId = $context->metadata['workflow_instance_id'] ?? null;

        if (! is_string($instancePublicId) || $instancePublicId === '') {
            return new WorkflowActionResult(
                status: WorkflowActionStatus::Waiting->value,
                warnings: ['Workflow instance id was missing from execution context.'],
                metadata: ['placeholder' => true],
                halt: true,
            );
        }

        $scope = new EnterpriseScope(
            organizationPublicId: $context->organizationPublicId,
            workspacePublicId: $context->workspacePublicId,
            moduleKey: $context->moduleKey,
        );

        $result = $this->humanTaskPort->createFromWorkflowNode(
            $scope,
            $instancePublicId,
            $nodeType,
            $node,
            $context,
            $variables,
            $this->resolveUserId($context),
            $this->resolveMembershipId($context),
        );

        if ($nodeType === WorkflowNodeType::Wait->value) {
            $timerConfig = $this->resolveTimerConfig($node);

            if ($timerConfig !== null) {
                try {
                    app(\App\Services\Enterprise\Workflow\Automation\WorkflowTimerService::class)->createFromWaitNode(
                        $scope,
                        $instancePublicId,
                        array_merge($node, ['timer' => $timerConfig]),
                        $context,
                    );
                } catch (\Throwable) {
                }
            }
        }

        return new WorkflowActionResult(
            status: WorkflowActionStatus::Waiting->value,
            metadata: [
                'human_task_public_id' => $result->task->publicId,
                'task_type' => $result->task->taskType,
                'approval_public_id' => $result->approval?->publicId,
                'node_type' => $nodeType,
            ],
            halt: true,
        );
    }

    public function supports(string $nodeType): bool
    {
        return in_array($nodeType, [WorkflowNodeType::Wait->value, WorkflowNodeType::Approval->value], true);
    }

    private function resolveUserId(WorkflowExecutionContext $context): ?string
    {
        if ($context->userPublicId === null) {
            return null;
        }

        return \App\Models\User::query()->where('public_id', $context->userPublicId)->value('id');
    }

    private function resolveMembershipId(WorkflowExecutionContext $context): ?string
    {
        if ($context->membershipPublicId === null) {
            return null;
        }

        return \App\Models\OrganizationMembership::query()
            ->where('public_id', $context->membershipPublicId)
            ->value('id');
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>|null
     */
    private function resolveTimerConfig(array $node): ?array
    {
        if (isset($node['timer']) && is_array($node['timer'])) {
            return $node['timer'];
        }

        if (isset($node['metadata']['timer']) && is_array($node['metadata']['timer'])) {
            return $node['metadata']['timer'];
        }

        return null;
    }
}
