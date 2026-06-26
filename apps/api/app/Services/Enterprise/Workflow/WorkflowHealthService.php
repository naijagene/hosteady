<?php

namespace App\Services\Enterprise\Workflow;

use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Services\Enterprise\Workflow\Runtime\WorkflowRuntimeHealthService;
use App\Support\Tenant\TenantContext;

class WorkflowHealthService
{
    public function __construct(
        private readonly WorkflowStatisticsService $statisticsService,
        private readonly WorkflowRuntimeHealthService $runtimeHealthService,
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
            ['workflow_definitions', 'workflow_categories', 'workflow_versions'],
            fn (): array => $this->assessWithTables($context, $enabled),
            array_merge($this->fallbackAssessment($enabled), [
                'runtime' => $this->runtimeHealthService->assess($context),
            ]),
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
                new \App\Modules\Sdk\Enterprise\Data\EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                ),
                $organizationId,
                $workspaceId,
            )
            : new \App\Modules\Sdk\Workflow\Data\WorkflowStatistics(0, 0, 0, 0, 0);

        if (! $enabled) {
            $warnings[] = 'Enterprise workflow is disabled in configuration.';
            $status = 'warning';
        }

        if ($stats->definitions === 0) {
            $warnings[] = 'No workflow definitions exist yet.';
            $status = 'warning';
        }

        $invalidPublished = $organizationId !== null
            ? $this->statisticsService->invalidPublishedCount($organizationId)
            : 0;

        if ($invalidPublished > 0) {
            $warnings[] = sprintf('%d published workflow definition(s) have invalid validation reports.', $invalidPublished);
            $status = 'critical';
        }

        return [
            'enabled' => $enabled,
            'definitions' => $stats->definitions,
            'published' => $stats->published,
            'drafts' => $stats->drafts,
            'archived' => $stats->archived,
            'categories' => $stats->categories,
            'runtime' => $this->runtimeHealthService->assess($context),
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
            'definitions' => 0,
            'published' => 0,
            'drafts' => 0,
            'archived' => 0,
            'categories' => 0,
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
            'definitions' => $assessment['definitions'],
            'published' => $assessment['published'],
            'drafts' => $assessment['drafts'],
            'archived' => $assessment['archived'],
            'categories' => $assessment['categories'],
            'runtime' => $this->runtimeHealthService->runtimeContribution($context),
        ];
    }
}
