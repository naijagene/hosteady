<?php

namespace App\Modules\Sdk\DataRepository\Contracts;

use App\Modules\Sdk\DataRepository\Data\EntityRecordQueryRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordQueryResult;

interface EntityRecordQueryProvider
{
    public function query(
        string $organizationId,
        ?string $workspaceId,
        EntityRecordQueryRequest $request,
    ): EntityRecordQueryResult;
}
