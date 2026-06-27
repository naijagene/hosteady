<?php

namespace App\Modules\Sdk\Entity\Data;

readonly class EntityQueryResult implements \JsonSerializable
{
    public function __construct(
        public string $moduleKey,
        public string $entityKey,
        public array $items = [],
        public int $total = 0,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            entityKey: (string) ($data['entity_key'] ?? ''),
            items: is_array($data['items'] ?? null) ? $data['items'] : [],
            total: (int) ($data['total'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'items' => $this->items,
            'total' => $this->total,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
