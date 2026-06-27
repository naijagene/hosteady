<?php

namespace App\Modules\Sdk\Table\Data;

readonly class TableQueryRequest implements \JsonSerializable
{
    /**
     * @param  list<TableFilter>  $filters
     * @param  list<TableSort>  $sorts
     * @param  list<string>  $columns
     */
    public function __construct(
        public string $moduleKey,
        public string $tableKey,
        public array $filters = [],
        public array $sorts = [],
        public ?string $search = null,
        public array $columns = [],
        public int $page = 1,
        public int $perPage = 25,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
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
            tableKey: (string) ($data['table_key'] ?? ''),
            filters: $filters,
            sorts: $sorts,
            search: isset($data['search']) ? (string) $data['search'] : null,
            columns: is_array($data['columns'] ?? null) ? array_values(array_map('strval', $data['columns'])) : [],
            page: max(1, (int) ($data['page'] ?? 1)),
            perPage: max(1, min(100, (int) ($data['per_page'] ?? $data['perPage'] ?? 25))),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'table_key' => $this->tableKey,
            'filters' => array_map(fn (TableFilter $f) => $f->toArray(), $this->filters),
            'sorts' => array_map(fn (TableSort $s) => $s->toArray(), $this->sorts),
            'search' => $this->search,
            'columns' => $this->columns,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
