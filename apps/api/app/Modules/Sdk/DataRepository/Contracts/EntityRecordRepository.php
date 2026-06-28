<?php

namespace App\Modules\Sdk\DataRepository\Contracts;

use App\Modules\Sdk\DataRepository\Data\EntityRecord;
use App\Modules\Sdk\DataRepository\Data\EntityRecordCreateRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordDeleteRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordRestoreRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordUpdateRequest;
use App\Modules\Sdk\Entity\Data\EntityDefinition;

interface EntityRecordRepository
{
    public function resolveDefinition(string $moduleKey, string $entityKey): EntityDefinition;

    public function find(
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
        bool $withTrashed = false,
    ): ?EntityRecord;

    public function create(string $organizationId, ?string $workspaceId, EntityRecordCreateRequest $request): EntityRecord;

    public function update(string $organizationId, ?string $workspaceId, EntityRecordUpdateRequest $request): EntityRecord;

    public function delete(string $organizationId, ?string $workspaceId, EntityRecordDeleteRequest $request): EntityRecord;

    public function restore(string $organizationId, ?string $workspaceId, EntityRecordRestoreRequest $request): EntityRecord;
}
