<?php

namespace App\Modules\Sdk\Table\Contracts;

use App\Modules\Sdk\Table\Data\TableDefinition;
use App\Modules\Sdk\Table\Data\TableQueryRequest;
use App\Modules\Sdk\Table\Data\TableQueryResult;

interface TableQueryExecutor
{
    public function execute(TableQueryRequest $request, TableDefinition $definition): TableQueryResult;
}
