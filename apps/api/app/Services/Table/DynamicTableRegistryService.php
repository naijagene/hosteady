<?php

namespace App\Services\Table;

use App\Models\TableDefinition as TableDefinitionModel;
use App\Modules\Sdk\Table\Contracts\TableRegistry;
use App\Modules\Sdk\Table\Data\TableDefinition;
use App\Modules\Sdk\Table\Exceptions\TableNotFoundException;
use App\Modules\Sdk\Table\Exceptions\TableRegistryException;
use Illuminate\Support\Facades\DB;

class DynamicTableRegistryService implements TableRegistry
{
    public function __construct(
        private readonly DynamicTableValidationService $validator,
        private readonly DynamicTableAuditRecorder $auditRecorder,
        private readonly DynamicTableSearchIndexer $searchIndexer,
        private readonly DynamicTableWorkflowBridge $workflowBridge,
    ) {
    }

    public function register(mixed $source): TableDefinition
    {
        $definition = $this->resolveDefinitionSource($source);
        $this->validator->assertValid($definition);

        if (TableDefinitionModel::query()
            ->where('module_key', $definition->moduleKey)
            ->where('table_key', $definition->tableKey)
            ->whereNull('organization_id')
            ->whereNull('workspace_id')
            ->exists()) {
            throw new TableRegistryException(sprintf(
                'Table definition [%s.%s] is already registered.',
                $definition->moduleKey,
                $definition->tableKey,
            ));
        }

        return DB::transaction(function () use ($definition) {
            $model = new TableDefinitionModel;
            DynamicTableMapper::applyDefinition($model, $definition);
            $model->save();

            $this->auditRecorder->recordDefinitionRegistered($model);
            $this->searchIndexer->indexDefinitionBestEffort($model);
            $this->workflowBridge->triggerDefinitionRegisteredBestEffort($model);

            return DynamicTableMapper::toDefinition($model);
        });
    }

    public function update(TableDefinition $definition): TableDefinition
    {
        $this->validator->assertValid($definition);

        $model = TableDefinitionModel::query()
            ->where('module_key', $definition->moduleKey)
            ->where('table_key', $definition->tableKey)
            ->first();

        if ($model === null) {
            throw new TableNotFoundException(sprintf(
                'Table definition [%s.%s] was not found.',
                $definition->moduleKey,
                $definition->tableKey,
            ));
        }

        return DB::transaction(function () use ($model, $definition) {
            DynamicTableMapper::applyDefinition($model, $definition);
            $model->save();

            $this->auditRecorder->recordDefinitionUpdated($model);
            $this->searchIndexer->indexDefinitionBestEffort($model);
            $this->workflowBridge->triggerDefinitionUpdatedBestEffort($model);

            return DynamicTableMapper::toDefinition($model);
        });
    }

    public function find(string $moduleKey, string $tableKey): ?TableDefinition
    {
        $model = TableDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('table_key', $tableKey)
            ->first();

        return $model === null ? null : DynamicTableMapper::toDefinition($model);
    }

    public function findByPublicId(string $publicId): ?TableDefinition
    {
        $model = TableDefinitionModel::query()->where('public_id', $publicId)->first();

        return $model === null ? null : DynamicTableMapper::toDefinition($model);
    }

    /**
     * @return list<TableDefinition>
     */
    public function list(?string $moduleKey = null): array
    {
        $query = TableDefinitionModel::query()->orderBy('module_key')->orderBy('table_key');

        if ($moduleKey !== null) {
            $query->where('module_key', $moduleKey);
        }

        return $query->get()
            ->map(fn (TableDefinitionModel $model) => DynamicTableMapper::toDefinition($model))
            ->all();
    }

    /**
     * @return list<TableDefinition>
     */
    public function findByEntity(string $moduleKey, string $entityKey): array
    {
        return TableDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('entity_key', $entityKey)
            ->orderBy('table_key')
            ->get()
            ->map(fn (TableDefinitionModel $model) => DynamicTableMapper::toDefinition($model))
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $tables
     * @return list<TableDefinition>
     */
    public function registerFromManifestTables(array $tables, string $moduleKey): array
    {
        $registered = [];

        foreach ($tables as $table) {
            if (! is_array($table)) {
                continue;
            }

            $payload = array_merge($table, ['module_key' => $moduleKey]);
            $tableKey = (string) ($payload['table_key'] ?? $payload['key'] ?? '');

            if ($tableKey === '') {
                continue;
            }

            if ($this->find($moduleKey, $tableKey) !== null) {
                continue;
            }

            $registered[] = $this->register(TableDefinition::fromArray($payload));
        }

        return $registered;
    }

    public function findModel(string $moduleKey, string $tableKey): ?TableDefinitionModel
    {
        return TableDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('table_key', $tableKey)
            ->first();
    }

    private function resolveDefinitionSource(mixed $source): TableDefinition
    {
        if ($source instanceof TableDefinition) {
            return $source;
        }

        if (is_array($source)) {
            return TableDefinition::fromArray($source);
        }

        throw new TableRegistryException('Unsupported table definition source.');
    }
}
