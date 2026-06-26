<?php

namespace App\Services\Enterprise\Workflow\Human;

use App\Models\OrganizationMembership;
use App\Models\User;
use App\Models\WorkflowHumanTask;
use App\Models\WorkflowTaskAssignment;
use App\Modules\Sdk\Workflow\Human\Data\TaskAssignment;
use App\Modules\Sdk\Workflow\Human\Enums\HumanTaskStatus;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionContext;
use App\Support\Tenant\TenantContext;

class TaskAssignmentService
{
    public function __construct(
        private readonly DefaultAssignmentStrategy $defaultStrategy,
    ) {
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $variables
     */
    public function assignFromNode(
        TenantContext $context,
        WorkflowHumanTask $task,
        array $node,
        array $variables,
        WorkflowExecutionContext $executionContext,
    ): TaskAssignment {
        $assignmentConfig = is_array($node['assignment'] ?? null)
            ? $node['assignment']
            : ['type' => 'current_user'];

        $assignmentType = (string) ($assignmentConfig['type'] ?? 'current_user');
        $assignment = $this->defaultStrategy->resolve(
            $context,
            $task->public_id,
            $assignmentType,
            $assignmentConfig,
            $variables,
            $executionContext,
        );

        WorkflowTaskAssignment::query()->create([
            'workflow_human_task_id' => $task->id,
            'assignment_type' => $assignment->assignmentType,
            'assignee_user_id' => $this->resolveUserId($assignment->assigneeUserPublicId),
            'assignee_membership_id' => $this->resolveMembershipId($context, $assignment->assigneeMembershipPublicId),
            'role_key' => $assignment->roleKey,
            'assigned_by_user_id' => $context->user->id,
            'assigned_by_membership_id' => $context->membership->id,
            'assigned_at' => now(),
            'metadata' => $assignment->metadata,
        ]);

        $task->update([
            'status' => HumanTaskStatus::Assigned,
            'assignee_user_id' => $this->resolveUserId($assignment->assigneeUserPublicId),
            'assignee_membership_id' => $this->resolveMembershipId($context, $assignment->assigneeMembershipPublicId),
            'assignee_role_key' => $assignment->roleKey,
            'assigned_at' => now(),
        ]);

        return $assignment;
    }

    private function resolveUserId(?string $userPublicId): ?string
    {
        if ($userPublicId === null) {
            return null;
        }

        return User::query()->where('public_id', $userPublicId)->value('id');
    }

    private function resolveMembershipId(TenantContext $context, ?string $membershipPublicId): ?string
    {
        if ($membershipPublicId === null) {
            return null;
        }

        return OrganizationMembership::query()
            ->where('public_id', $membershipPublicId)
            ->where('organization_id', $context->organization->id)
            ->value('id');
    }
}
