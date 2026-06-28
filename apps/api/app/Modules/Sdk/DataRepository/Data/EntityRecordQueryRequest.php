<?php

namespace App\Modules\Sdk\DataRepository\Data;

readonly class EntityRecordQueryRequest implements \JsonSerializable
{
    /**
     * @param  list<EntityRecordFilter>  $filters
     * @param  list<EntityRecordSort>  $sorts
     */
    public function __construct(
        public string $moduleKey,
        public string $entityKey,
        public array $filters = [],
        public array $sorts = [],
        public int $page = 1,
        public int $perPage = 25,
        public ?string $search = null,
        public ?EntityRecordProjection $projection = null,
        public bool $includeDeleted = false,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $filters = [];
        foreach (is_array($data['filters'] ?? null) ? $data['filters'] : [] as $filter) {
            if (is_array($filter)) {
                $filters[] = EntityRecordFilter::fromArray($filter);
            }
        }

        $sorts = [];
        foreach (is_array($data['sorts'] ?? null) ? $data['sorts'] : [] as $sort) {
            if (is_array($sort)) {
                $sorts[] = EntityRecordSort::fromArray($sort);
            }
        }

        $projection = null;
        if (is_array($data['projection'] ?? null)) {
            $projection = EntityRecordProjection::fromArray($data['projection']);
        }

        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            entityKey: (string) ($data['entity_key'] ?? ''),
            filters: $filters,
            sorts: $sorts,
            page: max(1, (int) ($data['page'] ?? 1)),
            perPage: max(1, (int) ($data['per_page'] ?? 25)),
            search: isset($data['search']) ? (string) $data['search'] : null,
            projection: $projection,
            includeDeleted: (bool) ($data['include_deleted'] ?? false),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'filters' => array_map(fn (EntityRecordFilter $filter) => $filter->toArray(), $this->filters),
            'sorts' => array_map(fn (EntityRecordSort $sort) => $sort->toArray(), $this->sorts),
            'page' => $this->page,
            'per_page' => $this->perPage,
            'search' => $this->search,
            'projection' => $this->projection?->toArray(),
            'include_deleted' => $this->includeDeleted,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
