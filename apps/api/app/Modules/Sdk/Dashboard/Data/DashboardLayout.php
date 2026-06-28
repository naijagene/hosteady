<?php

namespace App\Modules\Sdk\Dashboard\Data;

readonly class DashboardLayout implements \JsonSerializable
{
    /**
     * @param  list<DashboardLayoutItem>  $items
     */
    public function __construct(
        public array $items = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $items = [];
        foreach (is_array($data['items'] ?? null) ? $data['items'] : [] as $item) {
            if (is_array($item)) {
                $items[] = DashboardLayoutItem::fromArray($item);
            }
        }

        return new self(
            items: $items,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'items' => array_map(fn (DashboardLayoutItem $i) => $i->toArray(), $this->items),
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
