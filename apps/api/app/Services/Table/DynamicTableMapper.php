<?php

namespace App\Services\Table;

use App\Models\TableActivityLog;
use App\Models\TableDefinition as TableDefinitionModel;
use App\Models\TableView;
use App\Modules\Sdk\Table\Data\TableAction;
use App\Modules\Sdk\Table\Data\TableColumn;
use App\Modules\Sdk\Table\Data\TableDefinition;
use App\Modules\Sdk\Table\Data\TableFilter;
use App\Modules\Sdk\Table\Data\TableSort;
use App\Modules\Sdk\Table\Data\TableView as TableViewDto;

class DynamicTableMapper
{
    public static function toDefinition(TableDefinitionModel $model): TableDefinition
    {
        $columns = [];
        foreach (is_array($model->columns_json) ? $model->columns_json : [] as $column) {
            if (is_array($column)) {
                $columns[] = TableColumn::fromArray($column);
            }
        }

        $filters = [];
        foreach (is_array($model->filters_json) ? $model->filters_json : [] as $filter) {
            if (is_array($filter)) {
                $filters[] = TableFilter::fromArray($filter);
            }
        }

        $sorts = [];
        foreach (is_array($model->sorts_json) ? $model->sorts_json : [] as $sort) {
            if (is_array($sort)) {
                $sorts[] = TableSort::fromArray($sort);
            }
        }

        $actions = [];
        foreach (is_array($model->actions_json) ? $model->actions_json : [] as $action) {
            if (is_array($action)) {
                $actions[] = TableAction::fromArray($action);
            }
        }

        $views = [];
        foreach (is_array($model->views_json) ? $model->views_json : [] as $view) {
            if (is_array($view)) {
                $views[] = TableViewDto::fromArray($view);
            }
        }

        $defaultSort = null;
        if (is_array($model->default_sort_json) && $model->default_sort_json !== []) {
            $defaultSort = TableSort::fromArray($model->default_sort_json);
        }

        return new TableDefinition(
            moduleKey: $model->module_key,
            tableKey: $model->table_key,
            name: $model->name,
            publicId: $model->public_id,
            organizationId: $model->organization_id,
            workspaceId: $model->workspace_id,
            entityKey: $model->entity_key,
            description: $model->description,
            type: (string) $model->type,
            status: (string) $model->status,
            visibility: (string) $model->visibility,
            columns: $columns,
            filters: $filters,
            sorts: $sorts,
            defaultSort: $defaultSort,
            pagination: is_array($model->pagination_json) ? $model->pagination_json : [],
            actions: $actions,
            views: $views,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function applyDefinition(TableDefinitionModel $model, TableDefinition $definition): void
    {
        $model->fill([
            'module_key' => $definition->moduleKey,
            'table_key' => $definition->tableKey,
            'name' => $definition->name,
            'entity_key' => $definition->entityKey,
            'description' => $definition->description,
            'type' => $definition->type,
            'status' => $definition->status,
            'visibility' => $definition->visibility,
            'columns_json' => array_map(fn (TableColumn $c) => $c->toArray(), $definition->columns),
            'filters_json' => array_map(fn (TableFilter $f) => $f->toArray(), $definition->filters),
            'sorts_json' => array_map(fn (TableSort $s) => $s->toArray(), $definition->sorts),
            'default_sort_json' => $definition->defaultSort?->toArray(),
            'pagination_json' => $definition->pagination,
            'actions_json' => array_map(fn (TableAction $a) => $a->toArray(), $definition->actions),
            'views_json' => array_map(fn (TableViewDto $v) => $v->toArray(), $definition->views),
            'metadata' => $definition->metadata,
        ]);
    }

    /**
     * @return array{public_id: string}
     */
    public static function toReference(TableDefinitionModel $model): array
    {
        return [
            'public_id' => $model->public_id,
        ];
    }

    public static function toView(TableView $model): TableViewDto
    {
        $columns = [];
        foreach (is_array($model->columns_json) ? $model->columns_json : [] as $column) {
            if (is_array($column)) {
                $columns[] = TableColumn::fromArray($column);
            }
        }

        $filters = [];
        foreach (is_array($model->filters_json) ? $model->filters_json : [] as $filter) {
            if (is_array($filter)) {
                $filters[] = TableFilter::fromArray($filter);
            }
        }

        $sorts = [];
        foreach (is_array($model->sorts_json) ? $model->sorts_json : [] as $sort) {
            if (is_array($sort)) {
                $sorts[] = TableSort::fromArray($sort);
            }
        }

        return new TableViewDto(
            moduleKey: $model->module_key,
            tableKey: $model->table_key,
            name: $model->name,
            publicId: $model->public_id,
            organizationId: $model->organization_id,
            workspaceId: $model->workspace_id,
            tableDefinitionId: $model->table_definition_id,
            columns: $columns,
            filters: $filters,
            sorts: $sorts,
            isDefault: (bool) $model->is_default,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toActivityReference(TableActivityLog $model): array
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
