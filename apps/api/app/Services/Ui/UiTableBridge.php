<?php

namespace App\Services\Ui;

use App\Services\Table\DynamicTableRegistryService;

class UiTableBridge
{
    public function __construct(
        private readonly DynamicTableRegistryService $tableRegistry,
    ) {
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>|null
     */
    public function resolveReferenceBestEffort(?string $moduleKey, ?string $resourceKey, array $config = []): ?array
    {
        try {
            $moduleKey = $moduleKey ?? (string) ($config['module_key'] ?? '');
            $tableKey = $resourceKey ?? (string) ($config['table_key'] ?? $config['resource_key'] ?? '');

            if ($moduleKey === '' || $tableKey === '') {
                return null;
            }

            $definition = $this->tableRegistry->find($moduleKey, $tableKey);

            return $definition?->toArray();
        } catch (\Throwable) {
            return null;
        }
    }
}
