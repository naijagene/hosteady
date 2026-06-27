<?php

namespace App\Modules\Sdk\Table\Data;

readonly class TableQueryResult implements \JsonSerializable
{
    /**
     * @param  list<TableRow>  $rows
     */
    public function __construct(
        public string $moduleKey,
        public string $tableKey,
        public array $rows = [],
        public int $total = 0,
        public int $page = 1,
        public int $perPage = 25,
        public int $totalPages = 0,
        public array $appliedFilters = [],
        public array $appliedSorts = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $rows = [];
        foreach (is_array($data['rows'] ?? null) ? $data['rows'] : [] as $row) {
            if (is_array($row)) {
                $rows[] = TableRow::fromArray($row);
            }
        }

        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            tableKey: (string) ($data['table_key'] ?? ''),
            rows: $rows,
            total: (int) ($data['total'] ?? 0),
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 25),
            totalPages: (int) ($data['total_pages'] ?? 0),
            appliedFilters: is_array($data['applied_filters'] ?? null) ? $data['applied_filters'] : [],
            appliedSorts: is_array($data['applied_sorts'] ?? null) ? $data['applied_sorts'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'table_key' => $this->tableKey,
            'rows' => array_map(fn (TableRow $r) => $r->toArray(), $this->rows),
            'total' => $this->total,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total_pages' => $this->totalPages,
            'applied_filters' => $this->appliedFilters,
            'applied_sorts' => $this->appliedSorts,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
