<?php

namespace App\Services\Enterprise\Workflow\Human;

use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use App\Modules\Sdk\Workflow\Human\Contracts\TaskAssignmentStrategy;
use App\Modules\Sdk\Workflow\Human\Data\TaskAssignment;
use App\Modules\Sdk\Workflow\Human\Exceptions\TaskAssignmentException;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionContext;
use App\Support\Tenant\TenantContext;

class DefaultAssignmentStrategy implements TaskAssignmentStrategy
{
    /**
     * @var list<string>
     */
    private const SUPPORTED = [
        'direct_user',
        'role',
        'current_user',
        'manager',
        'dynamic_resolver',
        'round_robin',
    ];

    public function supports(string $assignmentType): bool
    {
        return in_array($assignmentType, self::SUPPORTED, true);
    }

    /**
     * @param  array<string, mixed>  $nodeConfig
     * @param  array<string, mixed>  $variables
     */
    public function resolve(
        TenantContext $context,
        string $taskPublicId,
        string $assignmentType,
        array $nodeConfig,
        array $variables,
        WorkflowExecutionContext $executionContext,
    ): TaskAssignment {
        return match ($assignmentType) {
            'direct_user' => $this->directUser($context, $taskPublicId, $nodeConfig),
            'role' => $this->roleAssignment($context, $taskPublicId, $nodeConfig),
            'current_user' => $this->currentUser($context, $taskPublicId, $executionContext),
            'manager' => $this->managerAssignment($context, $taskPublicId),
            'dynamic_resolver' => $this->placeholder($taskPublicId, $assignmentType, 'Dynamic resolver assignment is not implemented yet.'),
            'round_robin' => $this->placeholder($taskPublicId, $assignmentType, 'Round robin assignment is not implemented yet.'),
            default => throw new TaskAssignmentException(sprintf('Unsupported assignment type [%s].', $assignmentType)),
        };
    }

    /**
     * @param  array<string, mixed>  $nodeConfig
     */
    private function directUser(TenantContext $context, string $taskPublicId, array $nodeConfig): TaskAssignment
    {
        $membershipPublicId = $nodeConfig['membership_public_id'] ?? $nodeConfig['assignee_membership_public_id'] ?? null;
        $userPublicId = $nodeConfig['user_public_id'] ?? $nodeConfig['assignee_user_public_id'] ?? null;

        $membership = null;
        if (is_string($membershipPublicId)) {
            $membership = OrganizationMembership::query()
                ->where('public_id', $membershipPublicId)
                ->where('organization_id', $context->organization->id)
                ->first();
        } elseif (is_string($userPublicId)) {
            $user = User::query()->where('public_id', $userPublicId)->first();
            $membership = $user !== null
                ? OrganizationMembership::query()
                    ->where('organization_id', $context->organization->id)
                    ->where('user_id', $user->id)
                    ->first()
                : null;
        }

        if ($membership === null) {
            throw new TaskAssignmentException('Direct user assignment target was not found.');
        }

        return new TaskAssignment(
            publicId: (string) \Illuminate\Support\Str::uuid7(),
            assignmentType: 'direct_user',
            assigneeMembershipPublicId: $membership->public_id,
            assigneeUserPublicId: $membership->user?->public_id,
            assignedByMembershipPublicId: $context->membershipPublicId,
            assignedAt: now()->toIso8601String(),
            metadata: ['task_public_id' => $taskPublicId],
        );
    }

    /**
     * @param  array<string, mixed>  $nodeConfig
     */
    private function roleAssignment(TenantContext $context, string $taskPublicId, array $nodeConfig): TaskAssignment
    {
        $roleKey = (string) ($nodeConfig['role_key'] ?? $nodeConfig['role'] ?? 'manager');

        return new TaskAssignment(
            publicId: (string) \Illuminate\Support\Str::uuid7(),
            assignmentType: 'role',
            roleKey: $roleKey,
            assignedByMembershipPublicId: $context->membershipPublicId,
            assignedAt: now()->toIso8601String(),
            metadata: ['task_public_id' => $taskPublicId, 'placeholder' => false],
        );
    }

    private function currentUser(TenantContext $context, string $taskPublicId, WorkflowExecutionContext $executionContext): TaskAssignment
    {
        return new TaskAssignment(
            publicId: (string) \Illuminate\Support\Str::uuid7(),
            assignmentType: 'current_user',
            assigneeMembershipPublicId: $executionContext->membershipPublicId ?? $context->membershipPublicId,
            assigneeUserPublicId: $executionContext->userPublicId ?? $context->user->public_id,
            assignedByMembershipPublicId: $context->membershipPublicId,
            assignedAt: now()->toIso8601String(),
            metadata: ['task_public_id' => $taskPublicId],
        );
    }

    private function managerAssignment(TenantContext $context, string $taskPublicId): TaskAssignment
    {
        $managerRole = Role::query()
            ->where('organization_id', $context->organization->id)
            ->where('key', 'manager')
            ->first();

        if ($managerRole === null) {
            throw new TaskAssignmentException('Manager role was not found for assignment.');
        }

        $membership = OrganizationMembership::query()
            ->where('organization_id', $context->organization->id)
            ->whereHas('memberRoles', fn ($query) => $query->where('role_id', $managerRole->id))
            ->first();

        return new TaskAssignment(
            publicId: (string) \Illuminate\Support\Str::uuid7(),
            assignmentType: 'manager',
            assigneeMembershipPublicId: $membership?->public_id,
            assigneeUserPublicId: $membership?->user?->public_id,
            roleKey: 'manager',
            assignedByMembershipPublicId: $context->membershipPublicId,
            assignedAt: now()->toIso8601String(),
            metadata: ['task_public_id' => $taskPublicId],
        );
    }

    private function placeholder(string $taskPublicId, string $assignmentType, string $message): TaskAssignment
    {
        return new TaskAssignment(
            publicId: (string) \Illuminate\Support\Str::uuid7(),
            assignmentType: $assignmentType,
            assignedAt: now()->toIso8601String(),
            metadata: ['task_public_id' => $taskPublicId, 'placeholder' => true, 'message' => $message],
        );
    }
}
