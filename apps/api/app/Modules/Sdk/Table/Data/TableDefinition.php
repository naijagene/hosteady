<?php

namespace App\Modules\Sdk\Table\Data;

use App\Modules\Sdk\Table\Enums\TableStatus;
use App\Modules\Sdk\Table\Enums\TableType;
use App\Modules\Sdk\Table\Enums\TableVisibility;

readonly class TableDefinition implements \JsonSerializable
{
    /**
     * @param  list<TableColumn>  $columns
     * @param  list<TableFilter>  $filters
     * @param  list<TableSort>  $sorts
     * @param  list<TableAction>  $actions
     * @param  list<TableView>  $views
     */
    public function __construct(
        public string $moduleKey,
        public string $tableKey,
        public string $name,
        public ?string $publicId = null,
        public ?string $organizationId = null,
        public ?string $workspaceId = null,
        public ?string $entityKey = null,
        public ?string $description = null,
        public string $type = TableType::List->value,
        public string $status = TableStatus::Registered->value,
        public string $visibility = TableVisibility::Organization->value,
        public array $columns = [],
        public array $filters = [],
        public array $sorts = [],
        public ?TableSort $defaultSort = null,
        public array $pagination = [],
        public array $actions = [],
        public array $views = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $columns = [];
        foreach (is_array($data['columns'] ?? null) ? $data['columns'] : [] as $column) {
            if (is_array($column)) {
                $columns[] = TableColumn::fromArray($column);
            }
        }

        $filters = [];
        foreach (is_array($data['filters'] ?? null) ? $data['filters'] : [] as $filter) {
            if (is_array($filter)) {
                $filters[] = TableFilter::fromArray($filter);
            }
        }

        $sorts = [];
        foreach (is_array($data['sorts'] ?? null) ? $data['sorts'] : [] as $sort) {
            if (is_array($sort)) {
                $sorts[] = TableSort::fromArray($sort);
            }
        }

        $actions = [];
        foreach (is_array($data['actions'] ?? null) ? $data['actions'] : [] as $action) {
            if (is_array($action)) {
                $actions[] = TableAction::fromArray($action);
            }
        }

        $views = [];
        foreach (is_array($data['views'] ?? null) ? $data['views'] : [] as $view) {
            if (is_array($view)) {
                $views[] = TableView::fromArray($view);
            }
        }

        $defaultSort = null;
        if (is_array($data['default_sort'] ?? null)) {
            $defaultSort = TableSort::fromArray($data['default_sort']);
        }

        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            tableKey: (string) ($data['table_key'] ?? $data['key'] ?? ''),
            name: (string) ($data['name'] ?? $data['label'] ?? ''),
            publicId: isset($data['public_id']) ? (string) $data['public_id'] : null,
            organizationId: isset($data['organization_id']) ? (string) $data['organization_id'] : null,
            workspaceId: isset($data['workspace_id']) ? (string) $data['workspace_id'] : null,
            entityKey: isset($data['entity_key']) ? (string) $data['entity_key'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            type: (string) ($data['type'] ?? TableType::List->value),
            status: (string) ($data['status'] ?? TableStatus::Registered->value),
            visibility: (string) ($data['visibility'] ?? TableVisibility::Organization->value),
            columns: $columns,
            filters: $filters,
            sorts: $sorts,
            defaultSort: $defaultSort,
            pagination: is_array($data['pagination'] ?? null) ? $data['pagination'] : [],
            actions: $actions,
            views: $views,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'organization_id' => $this->organizationId,
            'workspace_id' => $this->workspaceId,
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'table_key' => $this->tableKey,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'columns' => array_map(fn (TableColumn $c) => $c->toArray(), $this->columns),
            'filters' => array_map(fn (TableFilter $f) => $f->toArray(), $this->filters),
            'sorts' => array_map(fn (TableSort $s) => $s->toArray(), $this->sorts),
            'default_sort' => $this->defaultSort?->toArray(),
            'pagination' => $this->pagination,
            'actions' => array_map(fn (TableAction $a) => $a->toArray(), $this->actions),
            'views' => array_map(fn (TableView $v) => $v->toArray(), $this->views),
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
