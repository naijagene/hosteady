<?php

namespace App\Services\Enterprise\Workflow\Automation;

use App\Models\WorkflowAutomationRule;
use App\Models\WorkflowTimer;
use App\Models\WorkflowTriggerExecution;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowAutomationStatistics;
use App\Modules\Sdk\Workflow\Automation\Enums\WorkflowAutomationStatus;
use App\Modules\Sdk\Workflow\Automation\Enums\WorkflowTimerStatus;
use App\Modules\Sdk\Workflow\Automation\Enums\WorkflowTriggerExecutionStatus;

class WorkflowAutomationStatisticsService
{
    public function statistics(EnterpriseScope $scope, string $organizationId, ?string $workspaceId = null): WorkflowAutomationStatistics
    {
        $rulesQuery = WorkflowAutomationRule::query()
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at');

        if ($workspaceId !== null) {
            $rulesQuery->where(function ($builder) use ($workspaceId) {
                $builder->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        $activeRules = (clone $rulesQuery)->where('status', WorkflowAutomationStatus::Active)->count();
        $disabledRules = (clone $rulesQuery)->where('status', WorkflowAutomationStatus::Disabled)->count();

        $executionsQuery = WorkflowTriggerExecution::query()
            ->where('organization_id', $organizationId)
            ->whereDate('executed_at', today());

        if ($workspaceId !== null) {
            $executionsQuery->where(function ($builder) use ($workspaceId) {
                $builder->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        $triggerExecutionsToday = (clone $executionsQuery)->count();
        $failedTriggersToday = (clone $executionsQuery)
            ->where('status', WorkflowTriggerExecutionStatus::Failed)
            ->count();

        $timersQuery = WorkflowTimer::query()
            ->where('organization_id', $organizationId);

        if ($workspaceId !== null) {
            $timersQuery->where(function ($builder) use ($workspaceId) {
                $builder->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        $activeTimers = (clone $timersQuery)->where('status', WorkflowTimerStatus::Active)->count();
        $dueTimers = (clone $timersQuery)
            ->where('status', WorkflowTimerStatus::Active)
            ->where('due_at', '<=', now())
            ->count();

        return new WorkflowAutomationStatistics(
            activeRules: $activeRules,
            disabledRules: $disabledRules,
            triggerExecutionsToday: $triggerExecutionsToday,
            failedTriggersToday: $failedTriggersToday,
            activeTimers: $activeTimers,
            dueTimers: $dueTimers,
        );
    }
}
