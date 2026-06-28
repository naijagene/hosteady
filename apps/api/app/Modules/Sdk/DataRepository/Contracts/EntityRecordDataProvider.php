<?php

namespace App\Modules\Sdk\DataRepository\Contracts;

use App\Modules\Sdk\DataRepository\Data\EntityRecord;
use App\Modules\Sdk\DataRepository\Data\EntityRecordQueryRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordQueryResult;

interface EntityRecordDataProvider
{
    public function findRecord(
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
    ): ?EntityRecord;

    public function queryRecords(
        string $organizationId,
        ?string $workspaceId,
        EntityRecordQueryRequest $request,
    ): EntityRecordQueryResult;
}
