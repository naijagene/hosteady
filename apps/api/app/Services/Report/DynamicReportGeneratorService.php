<?php

namespace App\Services\Report;

use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;
use App\Modules\Sdk\Dashboard\Data\DashboardWidget;
use App\Modules\Sdk\Dashboard\Enums\DashboardWidgetType;
use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Modules\Sdk\Report\Contracts\ReportGenerator;
use App\Modules\Sdk\Report\Data\ReportAggregate;
use App\Modules\Sdk\Report\Data\ReportChart;
use App\Modules\Sdk\Report\Data\ReportColumn;
use App\Modules\Sdk\Report\Data\ReportDefinition;
use App\Modules\Sdk\Report\Data\ReportFilter;
use App\Modules\Sdk\Report\Data\ReportLayout;
use App\Modules\Sdk\Report\Data\ReportMetric;
use App\Modules\Sdk\Report\Data\ReportSort;
use App\Modules\Sdk\Report\Enums\ReportStatus;
use App\Modules\Sdk\Report\Enums\ReportType;
use App\Modules\Sdk\Report\Enums\ReportVisibility;
use App\Modules\Sdk\Table\Data\TableDefinition;
use App\Services\Dashboard\DynamicDashboardRegistryService;
use App\Services\Entity\EnterpriseEntityRegistryService;
use App\Services\Table\DynamicTableRegistryService;

class DynamicReportGeneratorService implements ReportGenerator
{
    public function __construct(
        private readonly EnterpriseEntityRegistryService $entityRegistryService,
        private readonly DynamicTableRegistryService $tableRegistryService,
        private readonly DynamicDashboardRegistryService $dashboardRegistryService,
    ) {
    }

    public function generateEntityReport(string $moduleKey, string $entityKey, string $reportType = 'list'): ReportDefinition
    {
        $entity = $this->entityRegistryService->find($moduleKey, $entityKey);

        if ($entity === null) {
            throw new \InvalidArgumentException(sprintf(
                'Entity definition [%s.%s] was not found.',
                $moduleKey,
                $entityKey,
            ));
        }

        return $this->generateFromEntity($entity, $reportType);
    }

    public function generateFromEntity(EntityDefinition $entity, string $reportType = 'list'): ReportDefinition
    {
        $reportKey = $reportType === 'summary'
            ? sprintf('%s_summary_report', $entity->entityKey)
            : sprintf('%s_list_report', $entity->entityKey);

        $tableKey = sprintf('%s_list', $entity->entityKey);
        $table = $this->tableRegistryService->find($entity->moduleKey, $tableKey);

        $columns = $table !== null ? $this->columnsFromTable($table) : $this->defaultEntityColumns($entity);
        $filters = $table !== null ? $this->filtersFromTable($table) : [];
        $sorts = $table !== null ? $this->sortsFromTable($table) : [];

        if ($reportType === 'summary') {
            return new ReportDefinition(
                moduleKey: $entity->moduleKey,
                reportKey: $reportKey,
                name: sprintf('%s Summary Report', $entity->name),
                entityKey: $entity->entityKey,
                description: $entity->description,
                type: ReportType::Summary->value,
                status: ReportStatus::Registered->value,
                visibility: ReportVisibility::Organization->value,
                layout: new ReportLayout(sections: [['key' => 'summary', 'type' => 'kpi']]),
                columns: [],
                filters: $filters,
                sorts: [],
                groups: [],
                aggregates: [
                    new ReportAggregate(key: 'total_count', function: 'count', label: 'Total Records'),
                ],
                metrics: [
                    new ReportMetric(
                        key: 'total_records',
                        label: 'Total Records',
                        format: 'number',
                        dataSourceType: 'entity_placeholder',
                        dataSourceConfig: [
                            'module_key' => $entity->moduleKey,
                            'entity_key' => $entity->entityKey,
                        ],
                    ),
                ],
                charts: [],
                metadata: [
                    'generated_from_entity' => true,
                    'entity_public_id' => $entity->publicId,
                    'report_variant' => 'summary',
                ],
            );
        }

        return new ReportDefinition(
            moduleKey: $entity->moduleKey,
            reportKey: $reportKey,
            name: sprintf('%s List Report', $entity->name),
            entityKey: $entity->entityKey,
            tableKey: $table?->tableKey,
            description: $entity->description,
            type: ReportType::List->value,
            status: ReportStatus::Registered->value,
            visibility: ReportVisibility::Organization->value,
            layout: new ReportLayout(sections: [['key' => 'table', 'type' => 'table']]),
            columns: $columns,
            filters: $filters,
            sorts: $sorts,
            groups: [],
            aggregates: [],
            metrics: [],
            charts: [],
            metadata: [
                'generated_from_entity' => true,
                'entity_public_id' => $entity->publicId,
                'report_variant' => 'list',
            ],
        );
    }

    public function generateFromTable(TableDefinition $table): ReportDefinition
    {
        return new ReportDefinition(
            moduleKey: $table->moduleKey,
            reportKey: sprintf('%s_report', $table->tableKey),
            name: sprintf('%s Report', $table->name),
            tableKey: $table->tableKey,
            entityKey: $table->entityKey,
            type: ReportType::Table->value,
            status: ReportStatus::Registered->value,
            visibility: ReportVisibility::Organization->value,
            columns: $this->columnsFromTable($table),
            filters: $this->filtersFromTable($table),
            sorts: $this->sortsFromTable($table),
            metadata: [
                'generated_from_table' => true,
                'table_public_id' => $table->publicId,
            ],
        );
    }

    public function generateFromDashboard(DashboardDefinition $dashboard): ReportDefinition
    {
        $metrics = [];
        $charts = [];
        $sections = [];

        foreach ($dashboard->widgets as $widget) {
            if ($widget->widgetType === DashboardWidgetType::KpiCard->value
                || $widget->widgetType === DashboardWidgetType::Statistic->value) {
                $metrics[] = new ReportMetric(
                    key: $widget->widgetKey,
                    label: $widget->name,
                    format: 'number',
                    dataSourceType: $widget->dataSourceType,
                    dataSourceConfig: $widget->dataSourceConfig,
                );
                $sections[] = ['key' => $widget->widgetKey, 'type' => 'kpi'];
            } elseif ($widget->chartType !== null) {
                $charts[] = new ReportChart(
                    key: $widget->widgetKey,
                    name: $widget->name,
                    chartType: (string) $widget->chartType,
                    dataSourceType: $widget->dataSourceType,
                    dataSourceConfig: $widget->dataSourceConfig,
                );
                $sections[] = ['key' => $widget->widgetKey, 'type' => 'chart'];
            } else {
                $sections[] = ['key' => $widget->widgetKey, 'type' => 'section'];
            }
        }

        return new ReportDefinition(
            moduleKey: $dashboard->moduleKey,
            reportKey: sprintf('%s_report', $dashboard->dashboardKey),
            name: sprintf('%s Report', $dashboard->name),
            dashboardKey: $dashboard->dashboardKey,
            entityKey: $dashboard->entityKey,
            type: ReportType::Dashboard->value,
            status: ReportStatus::Registered->value,
            visibility: ReportVisibility::Organization->value,
            layout: new ReportLayout(sections: $sections),
            metrics: $metrics,
            charts: $charts,
            filters: array_map(
                fn ($f) => ReportFilter::fromArray($f->toArray()),
                $dashboard->filters,
            ),
            metadata: [
                'generated_from_dashboard' => true,
                'dashboard_public_id' => $dashboard->publicId,
            ],
        );
    }

    public function generateDashboardReport(string $moduleKey, string $dashboardKey): ReportDefinition
    {
        $dashboard = $this->dashboardRegistryService->find($moduleKey, $dashboardKey);

        if ($dashboard === null) {
            throw new \InvalidArgumentException(sprintf(
                'Dashboard definition [%s.%s] was not found.',
                $moduleKey,
                $dashboardKey,
            ));
        }

        return $this->generateFromDashboard($dashboard);
    }

    /**
     * @return list<ReportColumn>
     */
    private function columnsFromTable(TableDefinition $table): array
    {
        return array_map(
            fn ($column) => new ReportColumn(
                key: $column->key,
                label: $column->label,
                type: $column->type,
                sortable: $column->sortable,
                filterable: $column->filterable,
                visible: $column->visible,
                width: $column->width,
                metadata: $column->metadata,
            ),
            $table->columns,
        );
    }

    /**
     * @return list<ReportFilter>
     */
    private function filtersFromTable(TableDefinition $table): array
    {
        return array_map(
            fn ($filter) => ReportFilter::fromArray($filter->toArray()),
            $table->filters,
        );
    }

    /**
     * @return list<ReportSort>
     */
    private function sortsFromTable(TableDefinition $table): array
    {
        return array_map(
            fn ($sort) => ReportSort::fromArray($sort->toArray()),
            $table->sorts,
        );
    }

    /**
     * @return list<ReportColumn>
     */
    private function defaultEntityColumns(EntityDefinition $entity): array
    {
        return [
            new ReportColumn(key: 'id', label: 'ID', type: 'text'),
            new ReportColumn(key: 'name', label: 'Name', type: 'text'),
            new ReportColumn(key: 'status', label: 'Status', type: 'status'),
            new ReportColumn(key: 'created_at', label: 'Created At', type: 'datetime'),
        ];
    }
}
