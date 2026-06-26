<?php

namespace App\Modules\Sdk\Enterprise\Contracts;

use App\Modules\Sdk\Enterprise\Data\ReferenceCatalogData;
use App\Modules\Sdk\Enterprise\Data\ReferenceItemData;

interface ModuleReferenceCatalogProvider
{
    public function catalog(): ReferenceCatalogData;

    /**
     * @return list<ReferenceItemData>
     */
    public function items(): array;
}
