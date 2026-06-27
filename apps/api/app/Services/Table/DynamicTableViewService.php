<?php

namespace App\Services\Table;

use App\Models\TableDefinition as TableDefinitionModel;
use App\Models\TableView as TableViewModel;
use App\Modules\Sdk\Table\Contracts\TableViewProvider;
use App\Modules\Sdk\Table\Data\TableColumn;
use App\Modules\Sdk\Table\Data\TableFilter;
use App\Modules\Sdk\Table\Data\TableSort;
use App\Modules\Sdk\Table\Data\TableView;
use App\Modules\Sdk\Table\Exceptions\TableNotFoundException;
use Illuminate\Support\Str;

class DynamicTableViewService implements TableViewProvider
{
    public function __construct(
        private readonly DynamicTableAuditRecorder $auditRecorder,
    ) {
    }

    /**
     * @return list<TableView>
     */
    public function listViews(string $moduleKey, string $tableKey, string $organizationId, ?string $workspaceId = null): array
    {
        $query = TableViewModel::query()
            ->where('module_key', $moduleKey)
            ->where('table_key', $tableKey)
            ->where('organization_id', $organizationId)
            ->orderBy('name');

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        } else {
            $query->whereNull('workspace_id');
        }

        return $query->get()
            ->map(fn (TableViewModel $model) => DynamicTableMapper::toView($model))
            ->all();
    }

    public function saveView(TableView $view): TableView
    {
        $definition = TableDefinitionModel::query()
            ->where('module_key', $view->moduleKey)
            ->where('table_key', $view->tableKey)
            ->first();

        if ($definition === null) {
            throw new TableNotFoundException(sprintf(
                'Table definition [%s.%s] was not found.',
                $view->moduleKey,
                $view->tableKey,
            ));
        }

        if ($view->isDefault) {
            TableViewModel::query()
                ->where('table_definition_id', $definition->id)
                ->where('organization_id', $view->organizationId)
                ->when($view->workspaceId !== null, fn ($q) => $q->where('workspace_id', $view->workspaceId))
                ->when($view->workspaceId === null, fn ($q) => $q->whereNull('workspace_id'))
                ->update(['is_default' => false]);
        }

        $model = $view->publicId !== null
            ? TableViewModel::query()->where('public_id', $view->publicId)->first()
            : null;

        if ($model === null) {
            $model = new TableViewModel([
                'id' => (string) Str::uuid7(),
            ]);
        }

        $model->fill([
            'organization_id' => $view->organizationId,
            'workspace_id' => $view->workspaceId,
            'table_definition_id' => $definition->id,
            'module_key' => $view->moduleKey,
            'table_key' => $view->tableKey,
            'name' => $view->name,
            'columns_json' => array_map(fn (TableColumn $c) => $c->toArray(), $view->columns),
            'filters_json' => array_map(fn (TableFilter $f) => $f->toArray(), $view->filters),
            'sorts_json' => array_map(fn (TableSort $s) => $s->toArray(), $view->sorts),
            'is_default' => $view->isDefault,
            'metadata' => $view->metadata,
        ]);
        $model->save();

        $saved = DynamicTableMapper::toView($model);
        $this->auditRecorder->recordViewSaved($saved);

        return $saved;
    }

    public function deleteView(string $viewPublicId, string $organizationId, ?string $workspaceId = null): void
    {
        $query = TableViewModel::query()
            ->where('public_id', $viewPublicId)
            ->where('organization_id', $organizationId);

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        } else {
            $query->whereNull('workspace_id');
        }

        $model = $query->first();

        if ($model === null) {
            throw new TableNotFoundException(sprintf('Table view [%s] was not found.', $viewPublicId));
        }

        $model->delete();
        $this->auditRecorder->recordViewDeleted($viewPublicId);
    }
}
