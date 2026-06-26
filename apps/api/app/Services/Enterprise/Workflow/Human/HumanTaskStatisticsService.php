<?php

namespace App\Services\Enterprise\Workflow\Human;

use App\Models\WorkflowHumanTask;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Human\Data\TaskStatistics;
use App\Modules\Sdk\Workflow\Human\Enums\ApprovalStatus;
use App\Modules\Sdk\Workflow\Human\Enums\HumanTaskStatus;

class HumanTaskStatisticsService
{
    public function statistics(EnterpriseScope $scope, string $organizationId, ?string $workspaceId = null): TaskStatistics
    {
        $query = WorkflowHumanTask::query()
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at');

        if ($workspaceId !== null) {
            $query->where(function ($builder) use ($workspaceId) {
                $builder->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        $pending = (clone $query)->whereIn('status', [HumanTaskStatus::Created, HumanTaskStatus::Assigned])->count();
        $assigned = (clone $query)->where('status', HumanTaskStatus::Assigned)->count();
        $completed = (clone $query)->where('status', HumanTaskStatus::Completed)->count();
        $overdue = (clone $query)
            ->whereNotIn('status', [HumanTaskStatus::Completed, HumanTaskStatus::Cancelled, HumanTaskStatus::Rejected])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();
        $pendingApprovals = (clone $query)
            ->where('task_type', 'approval')
            ->where('approval_status', ApprovalStatus::Pending)
            ->count();

        return new TaskStatistics(
            pending: $pending,
            assigned: $assigned,
            completed: $completed,
            overdue: $overdue,
            pendingApprovals: $pendingApprovals,
        );
    }
}
