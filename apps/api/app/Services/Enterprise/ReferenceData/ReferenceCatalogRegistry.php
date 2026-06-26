<?php

namespace App\Services\Enterprise\ReferenceData;

use App\Modules\Sdk\Enterprise\Data\ReferenceCatalogData;
use App\Modules\Sdk\Enterprise\Data\ReferenceItemData;

class ReferenceCatalogRegistry
{
    /**
     * @var array<string, ReferenceCatalogData>
     */
    private array $catalogs = [];

    /**
     * @var array<string, list<ReferenceItemData>>
     */
    private array $items = [];

    public function register(ReferenceCatalogData $catalog, array $items): void
    {
        $this->catalogs[$catalog->key] = $catalog;
        $this->items[$catalog->key] = $items;
    }

    public function catalog(string $key): ?ReferenceCatalogData
    {
        return $this->catalogs[$key] ?? null;
    }

    /**
     * @return list<ReferenceItemData>
     */
    public function items(string $key): array
    {
        return $this->items[$key] ?? [];
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->catalogs);
    }
}
