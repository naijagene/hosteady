<?php

namespace App\Modules\Sdk\Table\Contracts;

use App\Modules\Sdk\Table\Data\TableDefinition;

interface TableRenderer
{
    /**
     * @return array<string, mixed>
     */
    public function render(TableDefinition $definition, array $context = []): array;
}
