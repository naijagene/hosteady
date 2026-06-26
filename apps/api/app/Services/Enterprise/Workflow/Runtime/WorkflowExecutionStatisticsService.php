<?php

namespace App\Services\Enterprise\Workflow\Runtime;

use App\Models\WorkflowInstance;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionStatistics;
use App\Modules\Sdk\Workflow\Runtime\Enums\WorkflowInstanceStatus;

class WorkflowExecutionStatisticsService
{
    public function statistics(EnterpriseScope $scope, string $organizationId, ?string $workspaceId = null): WorkflowExecutionStatistics
    {
        $query = WorkflowInstance::query()
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at');

        if ($workspaceId !== null) {
            $query->where(function ($builder) use ($workspaceId) {
                $builder->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        $running = (clone $query)->whereIn('status', [
            WorkflowInstanceStatus::Running,
            WorkflowInstanceStatus::Waiting,
            WorkflowInstanceStatus::Pending,
        ])->count();

        $completedToday = (clone $query)
            ->where('status', WorkflowInstanceStatus::Completed)
            ->whereDate('completed_at', today())
            ->count();

        $failedToday = (clone $query)
            ->where('status', WorkflowInstanceStatus::Failed)
            ->whereDate('completed_at', today())
            ->count();

        $averageDuration = (clone $query)
            ->whereNotNull('duration_ms')
            ->avg('duration_ms');

        return new WorkflowExecutionStatistics(
            runningInstances: $running,
            completedToday: $completedToday,
            failedToday: $failedToday,
            activeExecutions: $running,
            averageDurationMs: $averageDuration !== null ? (int) round($averageDuration) : null,
        );
    }
}
