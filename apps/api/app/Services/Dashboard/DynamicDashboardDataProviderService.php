<?php

namespace App\Services\Dashboard;

use App\Modules\Sdk\Dashboard\Contracts\DashboardDataProvider;
use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;
use App\Modules\Sdk\Dashboard\Data\DashboardWidget;
use App\Modules\Sdk\Dashboard\Data\DashboardWidgetData;
use App\Services\DataRepository\EnterpriseEntityRecordDashboardBridge;

class DynamicDashboardDataProviderService implements DashboardDataProvider
{
    public function __construct(
        private readonly EnterpriseEntityRecordDashboardBridge $dashboardBridge,
    ) {
    }

    /**
     * @var list<string>
     */
    public const DATA_SOURCE_TYPES = [
        'static',
        'entity_count',
        'form_submission_count',
        'table_placeholder',
        'workflow_queue_placeholder',
        'approval_queue_placeholder',
        'activity_placeholder',
        'notification_placeholder',
        'custom_placeholder',
    ];

    public function resolve(DashboardWidget $widget, array $context = []): DashboardWidgetData
    {
        $type = $widget->dataSourceType ?? 'static';

        return match ($type) {
            'entity_count' => $this->entityCount($widget),
            'form_submission_count' => $this->formSubmissionCount($widget),
            'table_placeholder' => $this->tablePlaceholder($widget),
            'workflow_queue_placeholder' => $this->workflowQueuePlaceholder($widget),
            'approval_queue_placeholder' => $this->approvalQueuePlaceholder($widget),
            'activity_placeholder' => $this->activityPlaceholder($widget),
            'notification_placeholder' => $this->notificationPlaceholder($widget),
            'custom_placeholder' => $this->customPlaceholder($widget),
            default => $this->staticData($widget),
        };
    }

    /**
     * @return list<DashboardWidgetData>
     */
    public function resolveAll(DashboardDefinition $definition, array $context = []): array
    {
        return array_map(
            fn (DashboardWidget $widget) => $this->resolve($widget, $context),
            $definition->widgets,
        );
    }

    private function staticData(DashboardWidget $widget): DashboardWidgetData
    {
        return new DashboardWidgetData(
            widgetKey: $widget->widgetKey,
            value: $widget->dataSourceConfig['value'] ?? 0,
            metadata: ['source' => 'static', 'placeholder' => true],
        );
    }

    private function entityCount(DashboardWidget $widget): DashboardWidgetData
    {
        $moduleKey = $widget->dataSourceConfig['module_key'] ?? null;
        $entityKey = $widget->dataSourceConfig['entity_key'] ?? null;
        $metadata = [
            'source' => 'entity_count',
            'module_key' => $moduleKey,
            'entity_key' => $entityKey,
            'placeholder' => true,
        ];

        if (! app()->bound(\App\Support\Tenant\TenantContext::class) || $moduleKey === null || $entityKey === null) {
            return new DashboardWidgetData(
                widgetKey: $widget->widgetKey,
                value: 0,
                metadata: $metadata,
            );
        }

        $context = app(\App\Support\Tenant\TenantContext::class);
        $count = $this->dashboardBridge->entityCount(
            $context->organization->id,
            $context->workspace?->id,
            $widget,
        );
        $metadata['placeholder'] = false;

        return new DashboardWidgetData(
            widgetKey: $widget->widgetKey,
            value: $count,
            metadata: $metadata,
        );
    }

    private function formSubmissionCount(DashboardWidget $widget): DashboardWidgetData
    {
        return new DashboardWidgetData(
            widgetKey: $widget->widgetKey,
            value: 0,
            metadata: [
                'source' => 'form_submission_count',
                'module_key' => $widget->dataSourceConfig['module_key'] ?? null,
                'form_key' => $widget->dataSourceConfig['form_key'] ?? null,
                'placeholder' => true,
            ],
        );
    }

    private function tablePlaceholder(DashboardWidget $widget): DashboardWidgetData
    {
        return new DashboardWidgetData(
            widgetKey: $widget->widgetKey,
            rows: [],
            metadata: [
                'source' => 'table_placeholder',
                'module_key' => $widget->dataSourceConfig['module_key'] ?? null,
                'table_key' => $widget->dataSourceConfig['table_key'] ?? null,
                'placeholder' => true,
            ],
        );
    }

    private function workflowQueuePlaceholder(DashboardWidget $widget): DashboardWidgetData
    {
        return new DashboardWidgetData(
            widgetKey: $widget->widgetKey,
            rows: [],
            metadata: [
                'source' => 'workflow_queue_placeholder',
                'placeholder' => true,
            ],
        );
    }

    private function approvalQueuePlaceholder(DashboardWidget $widget): DashboardWidgetData
    {
        return new DashboardWidgetData(
            widgetKey: $widget->widgetKey,
            rows: [],
            metadata: [
                'source' => 'approval_queue_placeholder',
                'placeholder' => true,
            ],
        );
    }

    private function activityPlaceholder(DashboardWidget $widget): DashboardWidgetData
    {
        return new DashboardWidgetData(
            widgetKey: $widget->widgetKey,
            rows: [],
            metadata: [
                'source' => 'activity_placeholder',
                'placeholder' => true,
            ],
        );
    }

    private function notificationPlaceholder(DashboardWidget $widget): DashboardWidgetData
    {
        return new DashboardWidgetData(
            widgetKey: $widget->widgetKey,
            rows: [],
            metadata: [
                'source' => 'notification_placeholder',
                'placeholder' => true,
            ],
        );
    }

    private function customPlaceholder(DashboardWidget $widget): DashboardWidgetData
    {
        return new DashboardWidgetData(
            widgetKey: $widget->widgetKey,
            value: null,
            metadata: [
                'source' => 'custom_placeholder',
                'placeholder' => true,
            ],
        );
    }
}
