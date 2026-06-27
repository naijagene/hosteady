<?php

namespace App\Modules\Sdk\Table\Contracts;

use App\Modules\Sdk\Table\Data\TableDefinition;

interface TableDefinitionProvider
{
    public function moduleKey(): string;

    public function tableKey(): string;

    public function definition(): TableDefinition;
}
