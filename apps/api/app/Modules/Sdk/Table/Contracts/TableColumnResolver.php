<?php

namespace App\Modules\Sdk\Table\Contracts;

use App\Modules\Sdk\Table\Data\TableColumn;
use App\Modules\Sdk\Table\Data\TableDefinition;

interface TableColumnResolver
{
    /**
     * @return list<TableColumn>
     */
    public function resolve(TableDefinition $definition, array $context = []): array;
}
