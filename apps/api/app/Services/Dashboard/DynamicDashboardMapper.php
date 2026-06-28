<?php

namespace App\Services\Dashboard;

use App\Models\DashboardActivityLog;
use App\Models\DashboardDefinition as DashboardDefinitionModel;
use App\Models\DashboardView;
use App\Models\DashboardWidget as DashboardWidgetModel;
use App\Modules\Sdk\Dashboard\Data\DashboardAction;
use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;
use App\Modules\Sdk\Dashboard\Data\DashboardFilter;
use App\Modules\Sdk\Dashboard\Data\DashboardLayout;
use App\Modules\Sdk\Dashboard\Data\DashboardLayoutItem;
use App\Modules\Sdk\Dashboard\Data\DashboardMetric;
use App\Modules\Sdk\Dashboard\Data\DashboardView as DashboardViewDto;
use App\Modules\Sdk\Dashboard\Data\DashboardWidget;

class DynamicDashboardMapper
{
    public static function toDefinition(DashboardDefinitionModel $model, bool $includeWidgets = true): DashboardDefinition
    {
        $filters = [];
        foreach (is_array($model->filters_json) ? $model->filters_json : [] as $filter) {
            if (is_array($filter)) {
                $filters[] = DashboardFilter::fromArray($filter);
            }
        }

        $actions = [];
        foreach (is_array($model->actions_json) ? $model->actions_json : [] as $action) {
            if (is_array($action)) {
                $actions[] = DashboardAction::fromArray($action);
            }
        }

        $layout = null;
        if (is_array($model->layout_json) && $model->layout_json !== []) {
            $layout = DashboardLayout::fromArray($model->layout_json);
        }

        $widgets = [];
        if ($includeWidgets) {
            foreach ($model->widgets()->orderBy('sort_order')->get() as $widgetModel) {
                $widgets[] = self::toWidget($widgetModel);
            }
        }

        return new DashboardDefinition(
            moduleKey: $model->module_key,
            dashboardKey: $model->dashboard_key,
            name: $model->name,
            publicId: $model->public_id,
            organizationId: $model->organization_id,
            workspaceId: $model->workspace_id,
            entityKey: $model->entity_key,
            description: $model->description,
            type: (string) $model->type,
            status: (string) $model->status,
            visibility: (string) $model->visibility,
            layout: $layout,
            widgets: $widgets,
            filters: $filters,
            actions: $actions,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function applyDefinition(DashboardDefinitionModel $model, DashboardDefinition $definition): void
    {
        $model->fill([
            'module_key' => $definition->moduleKey,
            'dashboard_key' => $definition->dashboardKey,
            'name' => $definition->name,
            'entity_key' => $definition->entityKey,
            'description' => $definition->description,
            'type' => $definition->type,
            'status' => $definition->status,
            'visibility' => $definition->visibility,
            'layout_json' => $definition->layout?->toArray(),
            'filters_json' => array_map(fn (DashboardFilter $f) => $f->toArray(), $definition->filters),
            'actions_json' => array_map(fn (DashboardAction $a) => $a->toArray(), $definition->actions),
            'metadata' => $definition->metadata,
        ]);
    }

    public static function toWidget(DashboardWidgetModel $model): DashboardWidget
    {
        $filters = [];
        foreach (is_array($model->filters_json) ? $model->filters_json : [] as $filter) {
            if (is_array($filter)) {
                $filters[] = DashboardFilter::fromArray($filter);
            }
        }

        $actions = [];
        foreach (is_array($model->actions_json) ? $model->actions_json : [] as $action) {
            if (is_array($action)) {
                $actions[] = DashboardAction::fromArray($action);
            }
        }

        $metric = null;
        if (is_array($model->metric_json) && $model->metric_json !== []) {
            $metric = DashboardMetric::fromArray($model->metric_json);
        }

        $layout = null;
        if (is_array($model->layout_json) && $model->layout_json !== []) {
            $layout = DashboardLayoutItem::fromArray($model->layout_json);
        }

        return new DashboardWidget(
            widgetKey: $model->widget_key,
            name: $model->name,
            publicId: $model->public_id,
            dashboardDefinitionId: $model->dashboard_definition_id,
            description: $model->description,
            widgetType: (string) $model->widget_type,
            chartType: $model->chart_type,
            dataSourceType: $model->data_source_type,
            dataSourceConfig: is_array($model->data_source_config) ? $model->data_source_config : [],
            metric: $metric,
            filters: $filters,
            layout: $layout,
            actions: $actions,
            refreshMode: (string) $model->refresh_mode,
            metadata: is_array($model->metadata) ? $model->metadata : [],
            sortOrder: (int) $model->sort_order,
        );
    }

    public static function applyWidget(DashboardWidgetModel $model, DashboardWidget $widget): void
    {
        $model->fill([
            'widget_key' => $widget->widgetKey,
            'name' => $widget->name,
            'description' => $widget->description,
            'widget_type' => $widget->widgetType,
            'chart_type' => $widget->chartType,
            'data_source_type' => $widget->dataSourceType,
            'data_source_config' => $widget->dataSourceConfig,
            'metric_json' => $widget->metric?->toArray(),
            'filters_json' => array_map(fn (DashboardFilter $f) => $f->toArray(), $widget->filters),
            'layout_json' => $widget->layout?->toArray(),
            'actions_json' => array_map(fn (DashboardAction $a) => $a->toArray(), $widget->actions),
            'refresh_mode' => $widget->refreshMode,
            'metadata' => $widget->metadata,
            'sort_order' => $widget->sortOrder,
        ]);
    }

    /**
     * @return array{public_id: string}
     */
    public static function toReference(DashboardDefinitionModel $model): array
    {
        return [
            'public_id' => $model->public_id,
        ];
    }

    public static function toView(DashboardView $model): DashboardViewDto
    {
        $filters = [];
        foreach (is_array($model->filters_json) ? $model->filters_json : [] as $filter) {
            if (is_array($filter)) {
                $filters[] = DashboardFilter::fromArray($filter);
            }
        }

        $layout = null;
        if (is_array($model->layout_json) && $model->layout_json !== []) {
            $layout = DashboardLayout::fromArray($model->layout_json);
        }

        return new DashboardViewDto(
            name: $model->name,
            publicId: $model->public_id,
            organizationId: $model->organization_id,
            workspaceId: $model->workspace_id,
            dashboardDefinitionId: $model->dashboard_definition_id,
            layout: $layout,
            filters: $filters,
            isDefault: (bool) $model->is_default,
            metadata: is_array($model->metadata ?? null) ? $model->metadata : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toActivityReference(DashboardActivityLog $model): array
    {
        return [
            'public_id' => $model->public_id,
            'action' => $model->action,
            'before_state' => is_array($model->before_state) ? $model->before_state : [],
            'after_state' => is_array($model->after_state) ? $model->after_state : [],
            'metadata' => is_array($model->metadata) ? $model->metadata : [],
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }
}
