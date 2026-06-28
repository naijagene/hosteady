<?php

namespace App\Modules\Sdk\DataRepository\Contracts;

use App\Modules\Sdk\DataRepository\Data\EntityRecordCreateRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordDeleteRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordMutationResult;
use App\Modules\Sdk\DataRepository\Data\EntityRecordRestoreRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordUpdateRequest;

interface EntityRecordMutationHandler
{
    public function create(string $organizationId, ?string $workspaceId, EntityRecordCreateRequest $request): EntityRecordMutationResult;

    public function update(string $organizationId, ?string $workspaceId, EntityRecordUpdateRequest $request): EntityRecordMutationResult;

    public function delete(string $organizationId, ?string $workspaceId, EntityRecordDeleteRequest $request): EntityRecordMutationResult;

    public function restore(string $organizationId, ?string $workspaceId, EntityRecordRestoreRequest $request): EntityRecordMutationResult;
}
