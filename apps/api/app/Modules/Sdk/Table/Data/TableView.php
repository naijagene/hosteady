<?php

namespace App\Modules\Sdk\Table\Data;

readonly class TableView implements \JsonSerializable
{
    /**
     * @param  list<TableColumn>  $columns
     * @param  list<TableFilter>  $filters
     * @param  list<TableSort>  $sorts
     */
    public function __construct(
        public string $moduleKey,
        public string $tableKey,
        public string $name,
        public ?string $publicId = null,
        public ?string $organizationId = null,
        public ?string $workspaceId = null,
        public ?string $tableDefinitionId = null,
        public array $columns = [],
        public array $filters = [],
        public array $sorts = [],
        public bool $isDefault = false,
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

        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            tableKey: (string) ($data['table_key'] ?? $data['key'] ?? ''),
            name: (string) ($data['name'] ?? $data['label'] ?? ''),
            publicId: isset($data['public_id']) ? (string) $data['public_id'] : null,
            organizationId: isset($data['organization_id']) ? (string) $data['organization_id'] : null,
            workspaceId: isset($data['workspace_id']) ? (string) $data['workspace_id'] : null,
            tableDefinitionId: isset($data['table_definition_id']) ? (string) $data['table_definition_id'] : null,
            columns: $columns,
            filters: $filters,
            sorts: $sorts,
            isDefault: (bool) ($data['is_default'] ?? false),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'organization_id' => $this->organizationId,
            'workspace_id' => $this->workspaceId,
            'table_definition_id' => $this->tableDefinitionId,
            'module_key' => $this->moduleKey,
            'table_key' => $this->tableKey,
            'name' => $this->name,
            'columns' => array_map(fn (TableColumn $c) => $c->toArray(), $this->columns),
            'filters' => array_map(fn (TableFilter $f) => $f->toArray(), $this->filters),
            'sorts' => array_map(fn (TableSort $s) => $s->toArray(), $this->sorts),
            'is_default' => $this->isDefault,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
