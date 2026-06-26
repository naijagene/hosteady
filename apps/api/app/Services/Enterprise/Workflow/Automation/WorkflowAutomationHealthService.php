<?php

namespace App\Services\Enterprise\Workflow\Automation;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class WorkflowAutomationHealthService
{
    public function __construct(
        private readonly WorkflowAutomationStatisticsService $statisticsService,
        private readonly EnterpriseTableHealthGuard $tableGuard,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.automation.enabled', true);

        return $this->tableGuard->assessWhenTablesPresent(
            ['workflow_automation_rules', 'workflow_timers'],
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
            : new \App\Modules\Sdk\Workflow\Automation\Data\WorkflowAutomationStatistics(0, 0, 0, 0, 0, 0);

        if (! $enabled) {
            $warnings[] = 'Workflow automation is disabled in configuration.';
            $status = 'warning';
        }

        if ($stats->dueTimers > 0) {
            $warnings[] = sprintf('%d workflow timer(s) are due.', $stats->dueTimers);
            $status = 'warning';
        }

        if ($stats->failedTriggersToday > 0) {
            $warnings[] = sprintf('%d workflow trigger(s) failed today.', $stats->failedTriggersToday);
            $status = 'critical';
        }

        return [
            'enabled' => $enabled,
            'active_rules' => $stats->activeRules,
            'trigger_executions_today' => $stats->triggerExecutionsToday,
            'failed_triggers_today' => $stats->failedTriggersToday,
            'active_timers' => $stats->activeTimers,
            'due_timers' => $stats->dueTimers,
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
            'active_rules' => 0,
            'trigger_executions_today' => 0,
            'failed_triggers_today' => 0,
            'active_timers' => 0,
            'due_timers' => 0,
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
            'enabled' => $assessment['enabled'],
            'active_rules' => $assessment['active_rules'],
            'trigger_executions_today' => $assessment['trigger_executions_today'],
            'failed_triggers_today' => $assessment['failed_triggers_today'],
            'active_timers' => $assessment['active_timers'],
            'due_timers' => $assessment['due_timers'],
        ];
    }
}
