<?php

namespace App\Modules\Sdk\Table\Contracts;

use App\Modules\Sdk\Table\Data\TableDefinition;

interface TableRegistry
{
    public function register(mixed $source): TableDefinition;

    public function update(TableDefinition $definition): TableDefinition;

    public function find(string $moduleKey, string $tableKey): ?TableDefinition;

    public function findByPublicId(string $publicId): ?TableDefinition;

    /**
     * @return list<TableDefinition>
     */
    public function list(?string $moduleKey = null): array;

    /**
     * @return list<TableDefinition>
     */
    public function findByEntity(string $moduleKey, string $entityKey): array;

    /**
     * @param  list<array<string, mixed>>  $tables
     * @return list<TableDefinition>
     */
    public function registerFromManifestTables(array $tables, string $moduleKey): array;
}
