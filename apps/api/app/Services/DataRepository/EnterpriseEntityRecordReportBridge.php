<?php

namespace App\Services\DataRepository;

use App\Modules\Sdk\DataRepository\Data\EntityRecordQueryRequest;
use App\Modules\Sdk\Report\Data\ReportDefinition;

class EnterpriseEntityRecordReportBridge
{
    public function __construct(
        private readonly EnterpriseEntityRecordDataProviderService $dataProvider,
        private readonly EnterpriseEntityRecordProjectionService $projectionService,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rows(
        string $organizationId,
        ?string $workspaceId,
        ReportDefinition $definition,
    ): array {
        if ($definition->entityKey === null) {
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

        return array_map(
            fn ($record) => $this->projectionService->project($record),
            $result->records,
        );
    }

    public function count(
        string $organizationId,
        ?string $workspaceId,
        ReportDefinition $definition,
    ): int {
        if ($definition->entityKey === null) {
            return 0;
        }

        $result = $this->dataProvider->queryRecords(
            $organizationId,
            $workspaceId,
            new EntityRecordQueryRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                perPage: 1,
            ),
        );

        return $result->total;
    }
}
