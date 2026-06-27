<?php

namespace App\Services\Table;

use App\Models\TableDefinition as TableDefinitionModel;
use App\Modules\Sdk\Table\Data\TableDefinition;
use App\Modules\Sdk\Table\Exceptions\TableNotFoundException;
use App\Support\Tenant\TenantContext;

class DynamicTableDefinitionService
{
    public function __construct(
        private readonly DynamicTableRegistryService $registryService,
    ) {
    }

    public function create(TableDefinition $definition): TableDefinition
    {
        return $this->registryService->register($definition);
    }

    public function update(TableDefinition $definition): TableDefinition
    {
        return $this->registryService->update($definition);
    }

    public function delete(string $moduleKey, string $tableKey): void
    {
        $model = $this->resolveModel($moduleKey, $tableKey);
        $model->delete();
    }

    public function find(string $moduleKey, string $tableKey): TableDefinition
    {
        $definition = $this->registryService->find($moduleKey, $tableKey);

        if ($definition === null) {
            throw new TableNotFoundException(sprintf('Table [%s.%s] was not found.', $moduleKey, $tableKey));
        }

        return $definition;
    }

    public function findByPublicId(string $publicId): TableDefinition
    {
        $definition = $this->registryService->findByPublicId($publicId);

        if ($definition === null) {
            throw new TableNotFoundException(sprintf('Table [%s] was not found.', $publicId));
        }

        return $definition;
    }

    /**
     * @return list<TableDefinition>
     */
    public function list(?string $moduleKey = null): array
    {
        return $this->registryService->list($moduleKey);
    }

    /**
     * @return list<TableDefinition>
     */
    public function listByEntity(string $moduleKey, string $entityKey): array
    {
        return $this->registryService->findByEntity($moduleKey, $entityKey);
    }

    /**
     * @param  list<array<string, mixed>>  $tables
     * @return list<TableDefinition>
     */
    public function registerFromManifest(array $tables, string $moduleKey): array
    {
        return $this->registryService->registerFromManifestTables($tables, $moduleKey);
    }

    private function resolveModel(string $moduleKey, string $tableKey): TableDefinitionModel
    {
        $definition = $this->registryService->find($moduleKey, $tableKey);

        if ($definition === null) {
            throw new TableNotFoundException(sprintf('Table [%s.%s] was not found.', $moduleKey, $tableKey));
        }

        $query = TableDefinitionModel::query()->where('public_id', $definition->publicId);

        if (app()->bound(TenantContext::class)) {
            $context = app(TenantContext::class);
            $query->where('organization_id', $context->organization->id);

            if ($context->workspace !== null) {
                $query->where('workspace_id', $context->workspace->id);
            } else {
                $query->whereNull('workspace_id');
            }
        }

        $model = $query->first();

        if ($model === null) {
            throw new TableNotFoundException(sprintf('Table [%s.%s] was not found.', $moduleKey, $tableKey));
        }

        return $model;
    }
}
