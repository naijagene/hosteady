<?php

namespace App\Modules\Sdk\Entity\Data;

readonly class EntityQuery implements \JsonSerializable
{
    public function __construct(
        public string $moduleKey,
        public string $entityKey,
        public array $filters = [],
        public int $limit = 50,
        public int $offset = 0,
        public ?string $sort = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            entityKey: (string) ($data['entity_key'] ?? ''),
            filters: is_array($data['filters'] ?? null) ? $data['filters'] : [],
            limit: (int) ($data['limit'] ?? 50),
            offset: (int) ($data['offset'] ?? 0),
            sort: isset($data['sort']) ? (string) $data['sort'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'filters' => $this->filters,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'sort' => $this->sort,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
