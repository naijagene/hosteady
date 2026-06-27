<?php

namespace App\Services\Table;

use App\Modules\Sdk\Table\Data\TableDefinition;
use App\Modules\Sdk\Table\Data\TableQueryRequest;
use App\Modules\Sdk\Table\Exceptions\TableValidationException;

class DynamicTableValidationService
{
    public function validate(TableDefinition $definition): bool
    {
        $this->assertValid($definition);

        return true;
    }

    public function assertValid(TableDefinition $definition): void
    {
        if ($definition->moduleKey === '') {
            throw new TableValidationException('Module key is required.');
        }

        if ($definition->tableKey === '') {
            throw new TableValidationException('Table key is required.');
        }

        if ($definition->name === '') {
            throw new TableValidationException('Table name is required.');
        }

        if (! preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $definition->moduleKey)) {
            throw new TableValidationException('Module key format is invalid.');
        }

        if (! preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $definition->tableKey)) {
            throw new TableValidationException('Table key format is invalid.');
        }

        $columnKeys = [];
        foreach ($definition->columns as $column) {
            if ($column->key === '') {
                throw new TableValidationException('Column key is required.');
            }

            if (isset($columnKeys[$column->key])) {
                throw new TableValidationException(sprintf('Duplicate column key [%s].', $column->key));
            }

            $columnKeys[$column->key] = true;
        }
    }

    public function assertValidQuery(TableQueryRequest $request, TableDefinition $definition): void
    {
        if ($request->moduleKey !== $definition->moduleKey || $request->tableKey !== $definition->tableKey) {
            throw new TableValidationException('Query request does not match the table definition.');
        }

        if ($request->page < 1) {
            throw new TableValidationException('Page must be at least 1.');
        }

        if ($request->perPage < 1 || $request->perPage > 100) {
            throw new TableValidationException('Per page must be between 1 and 100.');
        }

        $columnKeys = [];
        foreach ($definition->columns as $column) {
            $columnKeys[$column->key] = true;
        }

        foreach ($request->filters as $filter) {
            if ($filter->columnKey !== '' && ! isset($columnKeys[$filter->columnKey])) {
                throw new TableValidationException(sprintf('Unknown filter column [%s].', $filter->columnKey));
            }
        }

        foreach ($request->sorts as $sort) {
            if ($sort->columnKey !== '' && ! isset($columnKeys[$sort->columnKey])) {
                throw new TableValidationException(sprintf('Unknown sort column [%s].', $sort->columnKey));
            }
        }
    }
}
