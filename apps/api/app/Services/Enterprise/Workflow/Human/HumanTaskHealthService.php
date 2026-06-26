<?php

namespace App\Services\Enterprise\Workflow\Human;

use App\Models\WorkflowHumanTask;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Human\Enums\ApprovalStatus;
use App\Modules\Sdk\Workflow\Human\Enums\HumanTaskStatus;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class HumanTaskHealthService
{
    public function __construct(
        private readonly HumanTaskStatisticsService $statisticsService,
        private readonly EnterpriseTableHealthGuard $tableGuard,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.human_tasks.enabled', true);

        return $this->tableGuard->assessWhenTablesPresent(
            ['workflow_human_tasks'],
            fn (): array => $this->assessWithTables($context, $enabled),
            $this->fallbackAssessment($enabled),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function assessWithTables(?TenantContext $context, bool $enabled): array
    {
        $warnings = [];
        $status = 'healthy';

        $organizationId = $context?->organization->id;
        $workspaceId = $context?->workspace->id;

        $stats = $organizationId !== null
            ? $this->statisticsService->statistics(
                new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
                $organizationId,
                $workspaceId,
            )
            : new \App\Modules\Sdk\Workflow\Human\Data\TaskStatistics(0, 0, 0, 0, 0);

        $staleAssigned = $organizationId !== null
            ? WorkflowHumanTask::query()
                ->where('organization_id', $organizationId)
                ->where('status', HumanTaskStatus::Assigned)
                ->where('assigned_at', '<', now()->subDays(7))
                ->count()
            : 0;

        if (! $enabled) {
            $warnings[] = 'Human tasks are disabled in configuration.';
            $status = 'warning';
        }

        if ($stats->overdue > 0) {
            $warnings[] = sprintf('%d human task(s) are overdue.', $stats->overdue);
            $status = 'warning';
        }

        if ($stats->pendingApprovals > 10 && $status !== 'critical') {
            $warnings[] = sprintf('%d approval(s) are pending.', $stats->pendingApprovals);
            $status = 'warning';
        }

        if ($staleAssigned > 0) {
            $warnings[] = sprintf('%d assigned task(s) appear stale.', $staleAssigned);
            if ($status !== 'critical') {
                $status = 'warning';
            }
        }

        $pendingApprovalTasks = $organizationId !== null
            ? WorkflowHumanTask::query()
                ->where('organization_id', $organizationId)
                ->where('task_type', 'approval')
                ->where('approval_status', ApprovalStatus::Pending)
                ->where('created_at', '<', now()->subDays(3))
                ->count()
            : 0;

        if ($pendingApprovalTasks > 0) {
            $warnings[] = sprintf('%d approval(s) have been pending for more than 3 days.', $pendingApprovalTasks);
            $status = 'critical';
        }

        return [
            'enabled' => $enabled,
            'pending' => $stats->pending,
            'assigned' => $stats->assigned,
            'completed' => $stats->completed,
            'overdue' => $stats->overdue,
            'approvals' => $stats->pendingApprovals,
            'warnings' => $warnings,
            'status' => $status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackAssessment(bool $enabled): array
    {
        return [
            'enabled' => $enabled,
            'pending' => 0,
            'assigned' => 0,
            'completed' => 0,
            'overdue' => 0,
            'approvals' => 0,
            'warnings' => [],
            'status' => 'healthy',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function runtimeContribution(?TenantContext $context = null): array
    {
        $assessment = $this->assess($context);

        return [
            'pending' => $assessment['pending'],
            'assigned' => $assessment['assigned'],
            'completed' => $assessment['completed'],
            'overdue' => $assessment['overdue'],
            'approvals' => $assessment['approvals'],
        ];
    }
}
