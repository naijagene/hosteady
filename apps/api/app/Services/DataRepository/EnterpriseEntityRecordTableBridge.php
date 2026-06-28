<?php

namespace App\Services\DataRepository;

use App\Modules\Sdk\DataRepository\Data\EntityRecordQueryRequest;
use App\Modules\Sdk\Table\Data\TableDefinition;

class EnterpriseEntityRecordTableBridge
{
    public function __construct(
        private readonly EnterpriseEntityRecordDataProviderService $dataProvider,
        private readonly EnterpriseEntityRecordProjectionService $projectionService,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchRows(
        string $organizationId,
        ?string $workspaceId,
        TableDefinition $definition,
    ): array {
        if ($definition->entityKey === null) {
            return [];
        }

        if (! EnterpriseEntityRecordMapper::entityBindingEnabled($definition->metadata)) {
            return [];
        }

        $result = $this->dataProvider->queryRecords(
            $organizationId,
            $workspaceId,
            new EntityRecordQueryRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                perPage: 1000,
            ),
        );

        return array_map(function ($record) {
            $projected = $this->projectionService->project($record);

            return array_merge(['public_id' => $record->publicId], $projected['values']);
        }, $result->records);
    }
}
