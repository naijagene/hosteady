<?php

namespace App\Modules\Sdk\DataRepository\Data;

readonly class EntityRecordQueryResult implements \JsonSerializable
{
    /**
     * @param  list<EntityRecord>  $records
     */
    public function __construct(
        public string $moduleKey,
        public string $entityKey,
        public array $records = [],
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
        $records = [];
        foreach (is_array($data['records'] ?? null) ? $data['records'] : [] as $record) {
            if (is_array($record)) {
                $records[] = EntityRecord::fromArray($record);
            }
        }

        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            entityKey: (string) ($data['entity_key'] ?? ''),
            records: $records,
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
            'entity_key' => $this->entityKey,
            'records' => array_map(fn (EntityRecord $record) => $record->toArray(), $this->records),
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
