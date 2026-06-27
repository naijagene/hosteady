<?php

namespace App\Modules\Sdk\Table\Contracts;

use App\Modules\Sdk\Table\Data\TableDefinition;
use App\Modules\Sdk\Table\Data\TableSort;

interface TableSortResolver
{
    /**
     * @param  list<TableSort>  $requested
     * @return list<TableSort>
     */
    public function resolve(TableDefinition $definition, array $requested = []): array;
}
