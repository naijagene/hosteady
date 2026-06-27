<?php

namespace App\Services\Table;

use App\Modules\Sdk\Table\Contracts\TableColumnResolver;
use App\Modules\Sdk\Table\Data\TableColumn;
use App\Modules\Sdk\Table\Data\TableDefinition;

class DynamicTableColumnResolver implements TableColumnResolver
{
    /**
     * @return list<TableColumn>
     */
    public function resolve(TableDefinition $definition, array $context = []): array
    {
        $requestedColumns = is_array($context['columns'] ?? null) ? $context['columns'] : [];

        if ($requestedColumns === []) {
            return $definition->columns;
        }

        $columnMap = [];
        foreach ($definition->columns as $column) {
            $columnMap[$column->key] = $column;
        }

        $resolved = [];
        foreach ($requestedColumns as $key) {
            if (is_string($key) && isset($columnMap[$key])) {
                $resolved[] = $columnMap[$key];
            }
        }

        return $resolved === [] ? $definition->columns : $resolved;
    }
}
