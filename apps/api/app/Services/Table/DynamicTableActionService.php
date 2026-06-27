<?php

namespace App\Services\Table;

use App\Modules\Sdk\Table\Data\TableAction;
use App\Modules\Sdk\Table\Data\TableDefinition;

class DynamicTableActionService
{
    /**
     * @return list<TableAction>
     */
    public function resolve(TableDefinition $definition, array $context = []): array
    {
        if ($definition->actions === []) {
            return [
                new TableAction(key: 'refresh', label: 'Refresh', type: 'toolbar'),
            ];
        }

        return $definition->actions;
    }
}
