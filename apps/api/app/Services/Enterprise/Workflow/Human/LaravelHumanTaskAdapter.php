<?php

namespace App\Services\Enterprise\Workflow\Human;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Models\WorkflowApprovalDecision;
use App\Models\WorkflowHumanTask;
use App\Models\WorkflowInstance;
use App\Models\WorkflowTaskComment;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Human\Contracts\ApprovalPort;
use App\Modules\Sdk\Workflow\Human\Contracts\HumanTaskPort;
use App\Modules\Sdk\Workflow\Human\Data\ApprovalDecision;
use App\Modules\Sdk\Workflow\Human\Data\ApprovalReference;
use App\Modules\Sdk\Workflow\Human\Data\HumanTaskReference;
use App\Modules\Sdk\Workflow\Human\Data\HumanTaskResult;
use App\Modules\Sdk\Workflow\Human\Data\TaskComment;
use App\Modules\Sdk\Workflow\Human\Data\TaskStatistics;
use App\Modules\Sdk\Workflow\Human\Enums\ApprovalDecisionType;
use App\Modules\Sdk\Workflow\Human\Enums\ApprovalStatus;
use App\Modules\Sdk\Workflow\Human\Enums\HumanTaskStatus;
use App\Modules\Sdk\Workflow\Human\Enums\TaskPriority;
use App\Modules\Sdk\Workflow\Human\Exceptions\ApprovalException;
use App\Modules\Sdk\Workflow\Human\Exceptions\HumanTaskException;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionContext;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Facades\DB;

class LaravelHumanTaskAdapter implements HumanTaskPort
{
    public function __construct(
        private readonly TaskAssignmentService $assignmentService,
        private readonly TaskHistoryService $historyService,
        private readonly HumanTaskStatisticsService $statisticsService,
        private readonly HumanTaskAuditRecorder $auditRecorder,
    ) {
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $variables
     */
    public function createFromWorkflowNode(
        EnterpriseScope $scope,
        string $workflowInstanceId,
        string $nodeType,
        array $node,
        WorkflowExecutionContext $context,
        array $variables,
        ?string $userId = null,
        ?string $membershipId = null,
    ): HumanTaskResult {
        $instance = $this->findInstance($scope, $workflowInstanceId);
        $taskType = $nodeType === 'approval' ? 'approval' : ($nodeType === 'wait' ? 'wait' : 'task');
        $nodeId = (string) ($node['id'] ?? 'unknown');
        $title = (string) ($node['title'] ?? $node['label'] ?? ucfirst($taskType).' task');
        $priority = TaskPriority::tryFrom((string) ($node['priority'] ?? 'normal')) ?? TaskPriority::Normal;

        return DB::transaction(function () use ($scope, $instance, $node, $context, $variables, $userId, $membershipId, $taskType, $nodeId, $title, $priority) {
            $task = WorkflowHumanTask::query()->create([
                'organization_id' => $instance->organization_id,
                'workspace_id' => $instance->workspace_id,
                'workflow_instance_id' => $instance->id,
                'node_id' => $nodeId,
                'task_type' => $taskType,
                'title' => $title,
                'description' => isset($node['description']) ? (string) $node['description'] : null,
                'status' => HumanTaskStatus::Created,
                'priority' => $priority,
                'approval_status' => $taskType === 'approval' ? ApprovalStatus::Pending : null,
                'due_at' => isset($node['due_at']) ? now()->parse($node['due_at']) : null,
                'metadata' => [
                    'node_type' => $taskType,
                    'variables' => $variables,
                ],
                'created_by_user_id' => $userId,
                'created_by_membership_id' => $membershipId,
            ]);

            $this->auditRecorder->recordCreated($task);

            $tenantContext = $this->tenantContextFromInstance($instance, $userId, $membershipId);
            $this->assignmentService->assignFromNode($tenantContext, $task->fresh(), $node, $variables, $context);
            $this->auditRecorder->recordAssigned($task->fresh());

            $approval = null;
            if ($taskType === 'approval') {
                $decision = WorkflowApprovalDecision::query()->create([
                    'workflow_human_task_id' => $task->id,
                    'status' => ApprovalStatus::Pending,
                ]);
                $this->auditRecorder->recordApprovalRequested($task->fresh());
                $approval = $this->toApprovalReference($task->fresh(['workflowInstance.definition', 'approvalDecision']));
            }

            return new HumanTaskResult(
                task: $this->toTaskReference($task->fresh(['workflowInstance.definition', 'assigneeMembership.user', 'assigneeUser'])),
                approval: $approval,
            );
        });
    }

    /**
     * @return list<HumanTaskReference>
     */
    public function list(EnterpriseScope $scope, ?string $status = null, int $limit = 50): array
    {
        $query = $this->scopedQuery($scope)
            ->with(['workflowInstance.definition', 'assigneeMembership.user', 'assigneeUser']);

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('created_at')->limit($limit)->get()
            ->map(fn (WorkflowHumanTask $task) => $this->toTaskReference($task))
            ->all();
    }

    public function get(EnterpriseScope $scope, string $publicId): HumanTaskReference
    {
        return $this->toTaskReference($this->findTask($scope, $publicId));
    }

    public function open(
        EnterpriseScope $scope,
        string $publicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): HumanTaskReference {
        $task = $this->findTask($scope, $publicId);
        $this->assertActive($task);

        $task->update([
            'status' => HumanTaskStatus::Opened,
            'opened_at' => now(),
        ]);

        $this->auditRecorder->recordOpened($task->fresh());

        return $this->toTaskReference($task->fresh(['workflowInstance.definition', 'assigneeMembership.user', 'assigneeUser']));
    }

    public function complete(
        EnterpriseScope $scope,
        string $publicId,
        ?string $userId = null,
        ?string $membershipId = null,
        ?array $result = null,
    ): HumanTaskReference {
        $task = $this->findTask($scope, $publicId);
        $this->assertActive($task);

        $metadata = $task->metadata ?? [];
        if ($result !== null) {
            $metadata['result'] = $result;
        }

        $task->update([
            'status' => HumanTaskStatus::Completed,
            'completed_at' => now(),
            'completed_by_user_id' => $userId,
            'completed_by_membership_id' => $membershipId,
            'metadata' => $metadata,
        ]);

        $this->auditRecorder->recordCompleted($task->fresh());

        return $this->toTaskReference($task->fresh(['workflowInstance.definition', 'assigneeMembership.user', 'assigneeUser']));
    }

    public function cancel(
        EnterpriseScope $scope,
        string $publicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): HumanTaskReference {
        $task = $this->findTask($scope, $publicId);
        $this->assertActive($task);

        $task->update([
            'status' => HumanTaskStatus::Cancelled,
            'completed_at' => now(),
            'completed_by_user_id' => $userId,
            'completed_by_membership_id' => $membershipId,
        ]);

        $this->auditRecorder->recordCancelled($task->fresh());

        return $this->toTaskReference($task->fresh(['workflowInstance.definition', 'assigneeMembership.user', 'assigneeUser']));
    }

    public function addComment(
        EnterpriseScope $scope,
        string $publicId,
        string $body,
        ?string $userId = null,
        ?string $membershipId = null,
    ): TaskComment {
        $task = $this->findTask($scope, $publicId);

        $comment = WorkflowTaskComment::query()->create([
            'workflow_human_task_id' => $task->id,
            'body' => $body,
            'created_by_user_id' => $userId,
            'created_by_membership_id' => $membershipId,
            'created_at' => now(),
        ]);

        $this->auditRecorder->recordCommented($task, $comment->public_id);

        return $this->toTaskComment($comment->fresh(['createdByMembership.user', 'createdByUser']));
    }

    /**
     * @return list<TaskComment>
     */
    public function listComments(EnterpriseScope $scope, string $publicId): array
    {
        $task = $this->findTask($scope, $publicId);

        return $task->comments()->with(['createdByMembership.user', 'createdByUser'])->orderBy('created_at')->get()
            ->map(fn (WorkflowTaskComment $comment) => $this->toTaskComment($comment))
            ->all();
    }

    /**
     * @return list<\App\Modules\Sdk\Workflow\Human\Data\TaskHistory>
     */
    public function history(EnterpriseScope $scope, string $publicId): array
    {
        $task = $this->findTask($scope, $publicId);
        $task->load(['assignments', 'comments', 'escalations', 'approvalDecision']);

        return $this->historyService->build($task);
    }

    public function statistics(EnterpriseScope $scope): TaskStatistics
    {
        return $this->statisticsService->statistics(
            $scope,
            $this->organizationId($scope),
            $this->workspaceId($scope, $this->organizationId($scope)),
        );
    }

    /**
     * @return list<HumanTaskReference>
     */
    public function inbox(EnterpriseScope $scope, string $inboxType, ?string $membershipId = null, int $limit = 50): array
    {
        $query = $this->scopedQuery($scope)
            ->with(['workflowInstance.definition', 'assigneeMembership.user', 'assigneeUser']);

        match ($inboxType) {
            'assigned' => $membershipId !== null
                ? $query->where('assignee_membership_id', $membershipId)
                : $query->whereNotNull('assignee_membership_id'),
            'pending' => $query->whereIn('status', [HumanTaskStatus::Created, HumanTaskStatus::Assigned, HumanTaskStatus::Opened, HumanTaskStatus::InProgress]),
            'approvals' => $query->where('task_type', 'approval')->where('approval_status', ApprovalStatus::Pending),
            'overdue' => $query
                ->whereNotIn('status', [HumanTaskStatus::Completed, HumanTaskStatus::Cancelled, HumanTaskStatus::Rejected])
                ->whereNotNull('due_at')
                ->where('due_at', '<', now()),
            default => null,
        };

        return $query->orderByDesc('created_at')->limit($limit)->get()
            ->map(fn (WorkflowHumanTask $task) => $this->toTaskReference($task))
            ->all();
    }

    /**
     * @return list<ApprovalReference>
     */
    public function listApprovals(EnterpriseScope $scope, ?string $status = null, int $limit = 50): array
    {
        $query = $this->scopedQuery($scope)
            ->where('task_type', 'approval')
            ->with(['workflowInstance.definition', 'approvalDecision']);

        if ($status !== null) {
            $query->where('approval_status', $status);
        }

        return $query->orderByDesc('created_at')->limit($limit)->get()
            ->map(fn (WorkflowHumanTask $task) => $this->toApprovalReference($task))
            ->all();
    }

    public function getApproval(EnterpriseScope $scope, string $publicId): ApprovalReference
    {
        $task = $this->findTask($scope, $publicId);

        if ($task->task_type !== 'approval') {
            throw new ApprovalException('The requested task is not an approval.');
        }

        return $this->toApprovalReference($task);
    }

    public function approve(
        EnterpriseScope $scope,
        string $publicId,
        ?string $comment = null,
        ?string $userId = null,
        ?string $membershipId = null,
    ): ApprovalDecision {
        return $this->decide($scope, $publicId, ApprovalDecisionType::Approve, $comment, $userId, $membershipId);
    }

    public function reject(
        EnterpriseScope $scope,
        string $publicId,
        ?string $comment = null,
        ?string $userId = null,
        ?string $membershipId = null,
    ): ApprovalDecision {
        return $this->decide($scope, $publicId, ApprovalDecisionType::Reject, $comment, $userId, $membershipId);
    }

    private function decide(
        EnterpriseScope $scope,
        string $publicId,
        ApprovalDecisionType $decisionType,
        ?string $comment,
        ?string $userId,
        ?string $membershipId,
    ): ApprovalDecision {
        $task = $this->findTask($scope, $publicId);

        if ($task->task_type !== 'approval') {
            throw new ApprovalException('The requested task is not an approval.');
        }

        if ($task->approval_status !== ApprovalStatus::Pending) {
            throw new ApprovalException('Approval has already been decided.');
        }

        return DB::transaction(function () use ($task, $decisionType, $comment, $userId, $membershipId) {
            $decision = $task->approvalDecision;

            if ($decision === null) {
                throw new ApprovalException('Approval decision record was not found.');
            }

            $approvalStatus = $decisionType === ApprovalDecisionType::Approve
                ? ApprovalStatus::Approved
                : ApprovalStatus::Rejected;

            $taskStatus = $decisionType === ApprovalDecisionType::Approve
                ? HumanTaskStatus::Completed
                : HumanTaskStatus::Rejected;

            $decision->update([
                'decision_type' => $decisionType,
                'status' => $approvalStatus,
                'decided_by_user_id' => $userId,
                'decided_by_membership_id' => $membershipId,
                'decided_at' => now(),
                'comment' => $comment,
            ]);

            $task->update([
                'status' => $taskStatus,
                'approval_status' => $approvalStatus,
                'completed_at' => now(),
                'completed_by_user_id' => $userId,
                'completed_by_membership_id' => $membershipId,
            ]);

            if ($decisionType === ApprovalDecisionType::Approve) {
                $this->auditRecorder->recordApprovalApproved($task->fresh());
                $this->auditRecorder->recordApprovalCompleted($task->fresh());
            } else {
                $this->auditRecorder->recordApprovalRejected($task->fresh());
            }

            return $this->toApprovalDecision($decision->fresh(['decidedByMembership']));
        });
    }

    private function findTask(EnterpriseScope $scope, string $publicId): WorkflowHumanTask
    {
        $task = $this->scopedQuery($scope)
            ->with(['workflowInstance.definition', 'assigneeMembership.user', 'assigneeUser', 'approvalDecision'])
            ->where('public_id', $publicId)
            ->first();

        if ($task === null) {
            throw new HumanTaskException(sprintf('Human task [%s] was not found.', $publicId));
        }

        return $task;
    }

    private function findInstance(EnterpriseScope $scope, string $publicId): WorkflowInstance
    {
        $organizationId = $this->organizationId($scope);
        $workspaceId = $this->workspaceId($scope, $organizationId);

        $query = WorkflowInstance::query()
            ->with(['definition', 'organization', 'workspace'])
            ->where('public_id', $publicId)
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at');

        $this->applyWorkspaceScope($query, $workspaceId);

        $instance = $query->first();

        if ($instance === null) {
            throw new HumanTaskException(sprintf('Workflow instance [%s] was not found.', $publicId));
        }

        return $instance;
    }

    private function scopedQuery(EnterpriseScope $scope)
    {
        $organizationId = $this->organizationId($scope);
        $workspaceId = $this->workspaceId($scope, $organizationId);

        $query = WorkflowHumanTask::query()
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at');

        $this->applyWorkspaceScope($query, $workspaceId);

        return $query;
    }

    private function tenantContextFromInstance(
        WorkflowInstance $instance,
        ?string $userId = null,
        ?string $membershipId = null,
    ): TenantContext {
        $organization = $instance->organization ?? Organization::query()->findOrFail($instance->organization_id);
        $workspace = $instance->workspace;

        if ($workspace === null && $instance->workspace_id !== null) {
            $workspace = Workspace::query()->findOrFail($instance->workspace_id);
        }

        if ($workspace === null) {
            $workspace = $organization->workspaces()->whereNull('deleted_at')->orderByDesc('is_default')->firstOrFail();
        }

        $membership = null;
        if ($membershipId !== null) {
            $membership = OrganizationMembership::query()->find($membershipId);
        }

        if ($membership === null && $userId !== null) {
            $membership = OrganizationMembership::query()
                ->where('organization_id', $organization->id)
                ->where('user_id', $userId)
                ->first();
        }

        if ($membership === null) {
            $membership = OrganizationMembership::query()
                ->where('organization_id', $organization->id)
                ->firstOrFail();
        }

        $user = $userId !== null
            ? User::query()->findOrFail($userId)
            : ($membership->user ?? User::query()->findOrFail($membership->user_id));

        return TenantContext::fromModels($user, $organization, $membership, $workspace);
    }

    private function assertActive(WorkflowHumanTask $task): void
    {
        if (in_array($task->status, [HumanTaskStatus::Completed, HumanTaskStatus::Cancelled, HumanTaskStatus::Rejected], true)) {
            throw new HumanTaskException('Human task cannot be modified in its current state.');
        }
    }

    private function organizationId(EnterpriseScope $scope): string
    {
        return (string) Organization::query()
            ->where('public_id', $scope->organizationPublicId)
            ->value('id');
    }

    private function workspaceId(EnterpriseScope $scope, string $organizationId): ?string
    {
        if ($scope->workspacePublicId === null) {
            return null;
        }

        return Workspace::query()
            ->where('public_id', $scope->workspacePublicId)
            ->where('organization_id', $organizationId)
            ->value('id');
    }

    private function applyWorkspaceScope($query, ?string $workspaceId): void
    {
        if ($workspaceId === null) {
            return;
        }

        $query->where(function ($builder) use ($workspaceId) {
            $builder->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
        });
    }

    private function toTaskReference(WorkflowHumanTask $task): HumanTaskReference
    {
        return new HumanTaskReference(
            publicId: $task->public_id,
            status: $task->status->value,
            taskType: $task->task_type,
            title: $task->title,
            description: $task->description,
            priority: $task->priority?->value,
            nodeId: $task->node_id,
            workflowInstancePublicId: $task->workflowInstance?->public_id,
            workflowDefinitionName: $task->workflowInstance?->definition?->name,
            assigneeMembershipPublicId: $task->assigneeMembership?->public_id,
            assigneeUserPublicId: $task->assigneeUser?->public_id,
            assigneeRoleKey: $task->assignee_role_key,
            assignedAt: $task->assigned_at?->toIso8601String(),
            openedAt: $task->opened_at?->toIso8601String(),
            completedAt: $task->completed_at?->toIso8601String(),
            dueAt: $task->due_at?->toIso8601String(),
            approvalStatus: $task->approval_status?->value,
            metadata: $task->metadata ?? [],
            createdAt: $task->created_at?->toIso8601String(),
        );
    }

    private function toApprovalReference(WorkflowHumanTask $task): ApprovalReference
    {
        $decision = $task->approvalDecision;

        return new ApprovalReference(
            publicId: $decision?->public_id ?? $task->public_id,
            status: $task->approval_status?->value ?? ApprovalStatus::Pending->value,
            title: $task->title,
            taskPublicId: $task->public_id,
            workflowInstancePublicId: $task->workflowInstance?->public_id,
            workflowDefinitionName: $task->workflowInstance?->definition?->name,
            requestedAt: $task->created_at?->toIso8601String(),
            decidedAt: $decision?->decided_at?->toIso8601String(),
            decidedByMembershipPublicId: $decision?->decidedByMembership?->public_id,
            decisionType: $decision?->decision_type?->value,
            metadata: $task->metadata ?? [],
        );
    }

    private function toApprovalDecision(WorkflowApprovalDecision $decision): ApprovalDecision
    {
        return new ApprovalDecision(
            publicId: $decision->public_id,
            decisionType: $decision->decision_type?->value ?? '',
            status: $decision->status->value,
            comment: $decision->comment,
            decidedByMembershipPublicId: $decision->decidedByMembership?->public_id,
            decidedAt: $decision->decided_at?->toIso8601String(),
        );
    }

    private function toTaskComment(WorkflowTaskComment $comment): TaskComment
    {
        return new TaskComment(
            publicId: $comment->public_id,
            body: $comment->body,
            authorMembershipPublicId: $comment->createdByMembership?->public_id,
            authorUserPublicId: $comment->createdByUser?->public_id,
            createdAt: $comment->created_at?->toIso8601String(),
        );
    }
}

class LaravelApprovalPortAdapter implements ApprovalPort
{
    public function __construct(
        private readonly LaravelHumanTaskAdapter $adapter,
    ) {
    }

    /**
     * @return list<ApprovalReference>
     */
    public function list(EnterpriseScope $scope, ?string $status = null, int $limit = 50): array
    {
        return $this->adapter->listApprovals($scope, $status, $limit);
    }

    public function get(EnterpriseScope $scope, string $publicId): ApprovalReference
    {
        return $this->adapter->getApproval($scope, $publicId);
    }

    public function approve(
        EnterpriseScope $scope,
        string $publicId,
        ?string $comment = null,
        ?string $userId = null,
        ?string $membershipId = null,
    ): ApprovalDecision {
        return $this->adapter->approve($scope, $publicId, $comment, $userId, $membershipId);
    }

    public function reject(
        EnterpriseScope $scope,
        string $publicId,
        ?string $comment = null,
        ?string $userId = null,
        ?string $membershipId = null,
    ): ApprovalDecision {
        return $this->adapter->reject($scope, $publicId, $comment, $userId, $membershipId);
    }
}
