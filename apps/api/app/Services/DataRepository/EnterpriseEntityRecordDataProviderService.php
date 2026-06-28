<?php

namespace App\Services\DataRepository;

use App\Modules\Sdk\DataRepository\Contracts\EntityRecordDataProvider;
use App\Modules\Sdk\DataRepository\Contracts\EntityRecordQueryProvider;
use App\Modules\Sdk\DataRepository\Contracts\EntityRecordRepository;
use App\Modules\Sdk\DataRepository\Data\EntityRecord;
use App\Modules\Sdk\DataRepository\Data\EntityRecordQueryRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordQueryResult;

class EnterpriseEntityRecordDataProviderService implements EntityRecordDataProvider
{
    public function __construct(
        private readonly EntityRecordRepository $repository,
        private readonly EntityRecordQueryProvider $queryProvider,
    ) {
    }

    public function findRecord(
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
    ): ?EntityRecord {
        return $this->repository->find($organizationId, $workspaceId, $moduleKey, $entityKey, $recordPublicId);
    }

    public function queryRecords(
        string $organizationId,
        ?string $workspaceId,
        EntityRecordQueryRequest $request,
    ): EntityRecordQueryResult {
        return $this->queryProvider->query($organizationId, $workspaceId, $request);
    }
}
