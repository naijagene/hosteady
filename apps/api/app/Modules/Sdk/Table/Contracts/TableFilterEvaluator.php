<?php

namespace App\Modules\Sdk\Table\Contracts;

use App\Modules\Sdk\Table\Data\TableFilter;

interface TableFilterEvaluator
{
    public function evaluate(TableFilter $filter, mixed $value): bool;
}
