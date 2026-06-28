<?php

namespace App\Services\Report;

use App\Modules\Sdk\Report\Contracts\ReportDataProvider;
use App\Modules\Sdk\Report\Data\ReportDataset;
use App\Modules\Sdk\Report\Data\ReportDefinition;
use App\Services\DataRepository\EnterpriseEntityRecordReportBridge;

class DynamicReportDataProviderService implements ReportDataProvider
{
    public function __construct(
        private readonly EnterpriseEntityRecordReportBridge $reportBridge,
    ) {
    }

    /**
     * @var list<string>
     */
    public const DATA_SOURCE_TYPES = [
        'static',
        'entity_placeholder',
        'table_placeholder',
        'dashboard_placeholder',
        'form_submission_count',
        'workflow_instance_count',
        'approval_task_count',
        'custom_placeholder',
    ];

    public function resolve(ReportDefinition $definition, array $context = []): ReportDataset
    {
        $sourceType = (string) ($definition->metadata['data_source_type'] ?? $this->inferSourceType($definition));

        return match ($sourceType) {
            'entity_placeholder' => $this->entityPlaceholder($definition),
            'table_placeholder' => $this->tablePlaceholder($definition),
            'dashboard_placeholder' => $this->dashboardPlaceholder($definition),
            'form_submission_count' => $this->formSubmissionCount($definition),
            'workflow_instance_count' => $this->workflowInstanceCount($definition),
            'approval_task_count' => $this->approvalTaskCount($definition),
            'custom_placeholder' => $this->customPlaceholder($definition),
            default => $this->staticData($definition),
        };
    }

    private function inferSourceType(ReportDefinition $definition): string
    {
        if ($definition->dashboardKey !== null) {
            return 'dashboard_placeholder';
        }

        if ($definition->tableKey !== null) {
            return 'table_placeholder';
        }

        if ($definition->entityKey !== null) {
            return 'entity_placeholder';
        }

        return 'static';
    }

    private function staticData(ReportDefinition $definition): ReportDataset
    {
        return new ReportDataset(
            rows: [],
            metrics: $definition->metrics,
            charts: $definition->charts,
            metadata: ['source' => 'static', 'placeholder' => true],
        );
    }

    private function entityPlaceholder(ReportDefinition $definition): ReportDataset
    {
        if (! app()->bound(\App\Support\Tenant\TenantContext::class) || $definition->entityKey === null) {
            return new ReportDataset(
                rows: [],
                metrics: $definition->metrics,
                charts: $definition->charts,
                metadata: [
                    'source' => 'entity_placeholder',
                    'module_key' => $definition->moduleKey,
                    'entity_key' => $definition->entityKey,
                    'placeholder' => true,
                ],
            );
        }

        $context = app(\App\Support\Tenant\TenantContext::class);
        $rows = $this->reportBridge->rows(
            $context->organization->id,
            $context->workspace?->id,
            $definition,
        );
        $count = $this->reportBridge->count(
            $context->organization->id,
            $context->workspace?->id,
            $definition,
        );

        return new ReportDataset(
            rows: $rows,
            metrics: $definition->metrics,
            charts: $definition->charts,
            metadata: [
                'source' => 'entity_placeholder',
                'module_key' => $definition->moduleKey,
                'entity_key' => $definition->entityKey,
                'placeholder' => false,
                'count' => $count,
            ],
        );
    }

    private function tablePlaceholder(ReportDefinition $definition): ReportDataset
    {
        return new ReportDataset(
            rows: [],
            metrics: $definition->metrics,
            charts: $definition->charts,
            metadata: [
                'source' => 'table_placeholder',
                'module_key' => $definition->moduleKey,
                'table_key' => $definition->tableKey,
                'placeholder' => true,
            ],
        );
    }

    private function dashboardPlaceholder(ReportDefinition $definition): ReportDataset
    {
        return new ReportDataset(
            rows: [],
            metrics: $definition->metrics,
            charts: $definition->charts,
            metadata: [
                'source' => 'dashboard_placeholder',
                'module_key' => $definition->moduleKey,
                'dashboard_key' => $definition->dashboardKey,
                'placeholder' => true,
            ],
        );
    }

    private function formSubmissionCount(ReportDefinition $definition): ReportDataset
    {
        return new ReportDataset(
            rows: [],
            metrics: $definition->metrics,
            metadata: [
                'source' => 'form_submission_count',
                'placeholder' => true,
                'value' => 0,
            ],
        );
    }

    private function workflowInstanceCount(ReportDefinition $definition): ReportDataset
    {
        return new ReportDataset(
            rows: [],
            metrics: $definition->metrics,
            metadata: [
                'source' => 'workflow_instance_count',
                'placeholder' => true,
                'value' => 0,
            ],
        );
    }

    private function approvalTaskCount(ReportDefinition $definition): ReportDataset
    {
        return new ReportDataset(
            rows: [],
            metrics: $definition->metrics,
            metadata: [
                'source' => 'approval_task_count',
                'placeholder' => true,
                'value' => 0,
            ],
        );
    }

    private function customPlaceholder(ReportDefinition $definition): ReportDataset
    {
        return new ReportDataset(
            rows: [],
            metrics: $definition->metrics,
            charts: $definition->charts,
            metadata: ['source' => 'custom_placeholder', 'placeholder' => true],
        );
    }
}
