<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class SearchResult
{
    /**
     * @param  list<SearchIndexReference>  $items
     */
    public function __construct(
        public string $query,
        public int $total,
        public array $items,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'total' => $this->total,
            'items' => array_map(fn (SearchIndexReference $item) => $item->toArray(), $this->items),
        ];
    }
}
