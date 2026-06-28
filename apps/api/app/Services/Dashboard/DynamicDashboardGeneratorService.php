<?php

namespace App\Services\Dashboard;

use App\Modules\Sdk\Dashboard\Contracts\DashboardGenerator;
use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;
use App\Modules\Sdk\Dashboard\Data\DashboardLayout;
use App\Modules\Sdk\Dashboard\Data\DashboardLayoutItem;
use App\Modules\Sdk\Dashboard\Data\DashboardMetric;
use App\Modules\Sdk\Dashboard\Data\DashboardWidget;
use App\Modules\Sdk\Dashboard\Enums\DashboardRefreshMode;
use App\Modules\Sdk\Dashboard\Enums\DashboardStatus;
use App\Modules\Sdk\Dashboard\Enums\DashboardType;
use App\Modules\Sdk\Dashboard\Enums\DashboardVisibility;
use App\Modules\Sdk\Dashboard\Enums\DashboardWidgetType;
use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Services\Entity\EnterpriseEntityRegistryService;
use App\Services\Form\DynamicFormRegistryService;
use App\Services\Table\DynamicTableRegistryService;

class DynamicDashboardGeneratorService implements DashboardGenerator
{
    public function __construct(
        private readonly EnterpriseEntityRegistryService $entityRegistryService,
        private readonly DynamicFormRegistryService $formRegistryService,
        private readonly DynamicTableRegistryService $tableRegistryService,
    ) {
    }

    public function generateEntityDashboard(string $moduleKey, string $entityKey): DashboardDefinition
    {
        return $this->generate($moduleKey, $entityKey);
    }

    public function generate(string $moduleKey, string $entityKey): DashboardDefinition
    {
        $entity = $this->entityRegistryService->find($moduleKey, $entityKey);

        if ($entity === null) {
            throw new \InvalidArgumentException(sprintf(
                'Entity definition [%s.%s] was not found.',
                $moduleKey,
                $entityKey,
            ));
        }

        return $this->generateFromEntity($entity);
    }

    public function generateFromEntity(EntityDefinition $entity): DashboardDefinition
    {
        $dashboardKey = sprintf('%s_dashboard', $entity->entityKey);
        $widgets = $this->defaultWidgets($entity);
        $layoutItems = [];

        foreach ($widgets as $index => $widget) {
            $layoutItems[] = new DashboardLayoutItem(
                widgetKey: $widget->widgetKey,
                x: ($index % 3) * 4,
                y: intdiv($index, 3) * 2,
                width: 4,
                height: 2,
            );
        }

        return new DashboardDefinition(
            moduleKey: $entity->moduleKey,
            dashboardKey: $dashboardKey,
            name: sprintf('%s Dashboard', $entity->name),
            entityKey: $entity->entityKey,
            description: $entity->description,
            type: DashboardType::Entity->value,
            status: DashboardStatus::Registered->value,
            visibility: DashboardVisibility::Organization->value,
            layout: new DashboardLayout(items: $layoutItems),
            widgets: $widgets,
            filters: [],
            actions: [],
            metadata: [
                'generated_from_entity' => true,
                'entity_public_id' => $entity->publicId,
            ],
        );
    }

    /**
     * @return list<DashboardWidget>
     */
    private function defaultWidgets(EntityDefinition $entity): array
    {
        $widgets = [
            new DashboardWidget(
                widgetKey: 'total_records',
                name: 'Total Records',
                widgetType: DashboardWidgetType::KpiCard->value,
                dataSourceType: 'entity_count',
                dataSourceConfig: [
                    'module_key' => $entity->moduleKey,
                    'entity_key' => $entity->entityKey,
                ],
                metric: new DashboardMetric(
                    key: 'total_records',
                    label: 'Total Records',
                    format: 'number',
                    dataSourceType: 'entity_count',
                ),
                refreshMode: DashboardRefreshMode::OnLoad->value,
                sortOrder: 0,
            ),
            new DashboardWidget(
                widgetKey: 'recent_activity',
                name: 'Recent Activity',
                widgetType: DashboardWidgetType::ActivityFeed->value,
                dataSourceType: 'activity_placeholder',
                dataSourceConfig: [
                    'module_key' => $entity->moduleKey,
                    'entity_key' => $entity->entityKey,
                ],
                refreshMode: DashboardRefreshMode::OnLoad->value,
                sortOrder: 1,
            ),
        ];

        $formKey = sprintf('%s_create', $entity->entityKey);
        if ($this->formRegistryService->find($entity->moduleKey, $formKey) !== null) {
            $widgets[] = new DashboardWidget(
                widgetKey: 'recent_submissions',
                name: 'Recent Submissions',
                widgetType: DashboardWidgetType::Statistic->value,
                dataSourceType: 'form_submission_count',
                dataSourceConfig: [
                    'module_key' => $entity->moduleKey,
                    'form_key' => $formKey,
                ],
                metric: new DashboardMetric(
                    key: 'recent_submissions',
                    label: 'Recent Submissions',
                    format: 'number',
                    dataSourceType: 'form_submission_count',
                ),
                refreshMode: DashboardRefreshMode::OnLoad->value,
                sortOrder: 2,
            );
        }

        $tableKey = sprintf('%s_list', $entity->entityKey);
        if ($this->tableRegistryService->find($entity->moduleKey, $tableKey) !== null) {
            $widgets[] = new DashboardWidget(
                widgetKey: 'entity_table',
                name: sprintf('%s Table', $entity->name),
                widgetType: DashboardWidgetType::Table->value,
                dataSourceType: 'table_placeholder',
                dataSourceConfig: [
                    'module_key' => $entity->moduleKey,
                    'table_key' => $tableKey,
                ],
                refreshMode: DashboardRefreshMode::OnLoad->value,
                sortOrder: 3,
            );
        }

        $widgets[] = new DashboardWidget(
            widgetKey: 'workflow_queue',
            name: 'Workflow Queue',
            widgetType: DashboardWidgetType::WorkflowQueue->value,
            dataSourceType: 'workflow_queue_placeholder',
            dataSourceConfig: [
                'module_key' => $entity->moduleKey,
                'entity_key' => $entity->entityKey,
            ],
            refreshMode: DashboardRefreshMode::OnLoad->value,
            sortOrder: 4,
        );

        $widgets[] = new DashboardWidget(
            widgetKey: 'approval_queue',
            name: 'Approval Queue',
            widgetType: DashboardWidgetType::ApprovalQueue->value,
            dataSourceType: 'approval_queue_placeholder',
            dataSourceConfig: [
                'module_key' => $entity->moduleKey,
                'entity_key' => $entity->entityKey,
            ],
            refreshMode: DashboardRefreshMode::OnLoad->value,
            sortOrder: 5,
        );

        return $widgets;
    }
}
