<?php

namespace App\Services\Enterprise\Workflow\Runtime;

use App\Models\WorkflowInstance;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Runtime\Enums\WorkflowInstanceStatus;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class WorkflowRuntimeHealthService
{
    public function __construct(
        private readonly WorkflowExecutionStatisticsService $statisticsService,
        private readonly EnterpriseTableHealthGuard $tableGuard,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.workflow.enabled', true);

        return $this->tableGuard->assessWhenTablesPresent(
            ['workflow_instances'],
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
            : new \App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionStatistics(0, 0, 0, 0);

        $stalled = $organizationId !== null
            ? WorkflowInstance::query()
                ->where('organization_id', $organizationId)
                ->where('status', WorkflowInstanceStatus::Running)
                ->where('started_at', '<', now()->subHours(24))
                ->count()
            : 0;

        if (! $enabled) {
            $warnings[] = 'Workflow runtime is disabled in configuration.';
            $status = 'warning';
        }

        if ($stalled > 0) {
            $warnings[] = sprintf('%d workflow instance(s) appear stalled.', $stalled);
            $status = 'critical';
        }

        if ($stats->failedToday > 0 && $status !== 'critical') {
            $warnings[] = sprintf('%d workflow execution(s) failed today.', $stats->failedToday);
            $status = 'warning';
        }

        return [
            'enabled' => $enabled,
            'running' => $stats->runningInstances,
            'completed' => $stats->completedToday,
            'failed' => $stats->failedToday,
            'running_instances' => $stats->runningInstances,
            'completed_today' => $stats->completedToday,
            'failed_today' => $stats->failedToday,
            'average_duration' => $stats->averageDurationMs,
            'average_duration_ms' => $stats->averageDurationMs,
            'active_executions' => $stats->activeExecutions,
            'stalled_instances' => $stalled,
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
            'running' => 0,
            'completed' => 0,
            'failed' => 0,
            'running_instances' => 0,
            'completed_today' => 0,
            'failed_today' => 0,
            'average_duration' => null,
            'average_duration_ms' => null,
            'active_executions' => 0,
            'stalled_instances' => 0,
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
            'running_instances' => $assessment['running_instances'],
            'completed_today' => $assessment['completed_today'],
            'failed_today' => $assessment['failed_today'],
            'average_duration' => $assessment['average_duration'],
            'active_executions' => $assessment['active_executions'],
        ];
    }
}
