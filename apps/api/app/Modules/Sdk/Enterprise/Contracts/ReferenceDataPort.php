<?php

namespace App\Modules\Sdk\Enterprise\Contracts;

use App\Modules\Sdk\Enterprise\Data\ReferenceCatalogData;
use App\Modules\Sdk\Enterprise\Data\ReferenceItemData;

interface ReferenceDataPort
{
    public function catalog(string $catalogKey): ?ReferenceCatalogData;

    /**
     * @return list<ReferenceItemData>
     */
    public function listItems(string $catalogKey, bool $activeOnly = true): array;

    public function findItem(string $catalogKey, string $code): ?ReferenceItemData;

    public function registerCatalog(ReferenceCatalogData $catalog): void;
}
