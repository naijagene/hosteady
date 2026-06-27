<?php

namespace App\Services\Table;

use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Modules\Sdk\Entity\Data\EntityFieldDefinition;
use App\Modules\Sdk\Table\Data\TableAction;
use App\Modules\Sdk\Table\Data\TableColumn;
use App\Modules\Sdk\Table\Data\TableColumnOption;
use App\Modules\Sdk\Table\Data\TableDefinition;
use App\Modules\Sdk\Table\Data\TableSort;
use App\Modules\Sdk\Table\Enums\TableStatus;
use App\Modules\Sdk\Table\Enums\TableType;
use App\Modules\Sdk\Table\Enums\TableVisibility;
use App\Services\Entity\EnterpriseEntityRegistryService;

class DynamicTableGeneratorService
{
    public function __construct(
        private readonly EnterpriseEntityRegistryService $entityRegistryService,
    ) {
    }

    public function generateListTable(string $moduleKey, string $entityKey): TableDefinition
    {
        $entity = $this->entityRegistryService->find($moduleKey, $entityKey);

        if ($entity === null) {
            throw new \InvalidArgumentException(sprintf(
                'Entity definition [%s.%s] was not found.',
                $moduleKey,
                $entityKey,
            ));
        }

        return $this->generateFromEntity($entity, TableType::List->value);
    }

    public function generate(string $moduleKey, string $entityKey, string $tableType = 'list'): TableDefinition
    {
        $entity = $this->entityRegistryService->find($moduleKey, $entityKey);

        if ($entity === null) {
            throw new \InvalidArgumentException(sprintf(
                'Entity definition [%s.%s] was not found.',
                $moduleKey,
                $entityKey,
            ));
        }

        return $this->generateFromEntity($entity, $tableType);
    }

    public function generateFromEntity(EntityDefinition $entity, string $tableType = 'list'): TableDefinition
    {
        $tableKey = sprintf('%s_%s', $entity->entityKey, $tableType === TableType::List->value ? 'list' : $tableType);

        $columns = [];
        foreach ($entity->fields as $field) {
            if ($this->isListableField($field, $tableType)) {
                $columns[] = $this->mapEntityField($field);
            }
        }

        if ($columns === []) {
            $columns[] = new TableColumn(
                key: 'public_id',
                label: 'ID',
                type: 'uuid',
                sortable: true,
                filterable: false,
                searchable: false,
            );
        }

        return new TableDefinition(
            moduleKey: $entity->moduleKey,
            tableKey: $tableKey,
            name: sprintf('%s %s table', $entity->name, $tableType),
            entityKey: $entity->entityKey,
            description: $entity->description,
            type: $tableType,
            status: TableStatus::Registered->value,
            visibility: TableVisibility::Organization->value,
            columns: $columns,
            filters: [],
            sorts: [],
            defaultSort: new TableSort(columnKey: $columns[0]->key, direction: 'asc'),
            pagination: ['page' => 1, 'per_page' => 25],
            actions: $this->defaultActions($tableType),
            views: [],
            metadata: [
                'generated_from_entity' => true,
                'entity_public_id' => $entity->publicId,
            ],
        );
    }

    private function isListableField(EntityFieldDefinition $field, string $tableType): bool
    {
        if ($tableType !== TableType::List->value) {
            return true;
        }

        $entityType = strtolower($field->type);

        return $entityType !== 'computed' || ($field->searchable ?? false);
    }

    private function mapEntityField(EntityFieldDefinition $field): TableColumn
    {
        $type = $this->mapFieldType($field);
        $options = $this->resolveOptions($field);

        return new TableColumn(
            key: $field->key,
            label: $field->label,
            type: $type,
            sortable: $type !== 'json',
            filterable: true,
            searchable: $field->searchable,
            options: $options,
            metadata: array_merge($field->metadata, [
                'entity_field_type' => $field->type,
                'description' => $field->description,
            ]),
        );
    }

    private function mapFieldType(EntityFieldDefinition $field): string
    {
        $entityType = strtolower($field->type);

        if ($entityType === 'computed') {
            return 'display';
        }

        return match ($entityType) {
            'string', 'text' => 'text',
            'integer', 'decimal', 'number' => 'number',
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime' => 'datetime',
            'enum' => 'enum',
            'reference' => 'reference',
            'json' => 'json',
            'uuid' => 'uuid',
            default => 'text',
        };
    }

    /**
     * @return list<TableColumnOption>
     */
    private function resolveOptions(EntityFieldDefinition $field): array
    {
        if (strtolower($field->type) !== 'enum') {
            return [];
        }

        $options = [];
        $rawOptions = is_array($field->metadata['options'] ?? null) ? $field->metadata['options'] : [];

        foreach ($rawOptions as $option) {
            if (is_array($option)) {
                $options[] = TableColumnOption::fromArray($option);

                continue;
            }

            if (is_string($option) || is_numeric($option)) {
                $value = (string) $option;
                $options[] = new TableColumnOption(value: $value, label: $value);
            }
        }

        return $options;
    }

    /**
     * @return list<TableAction>
     */
    private function defaultActions(string $tableType): array
    {
        return match ($tableType) {
            TableType::List->value => [
                new TableAction(key: 'view', label: 'View', type: 'row'),
                new TableAction(key: 'edit', label: 'Edit', type: 'row'),
                new TableAction(key: 'create', label: 'Create', type: 'toolbar'),
                new TableAction(key: 'export', label: 'Export', type: 'toolbar'),
            ],
            TableType::Detail->value => [
                new TableAction(key: 'back', label: 'Back', type: 'toolbar'),
            ],
            default => [
                new TableAction(key: 'refresh', label: 'Refresh', type: 'toolbar'),
            ],
        };
    }
}
