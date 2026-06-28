<?php

namespace App\Modules\Sdk\DataRepository\Contracts;

use App\Modules\Sdk\DataRepository\Data\EntityRecordCreateRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordDeleteRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordRestoreRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordUpdateRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordValidationReport;
use App\Modules\Sdk\Entity\Data\EntityDefinition;

interface EntityRecordValidator
{
    public function validateCreate(EntityRecordCreateRequest $request, EntityDefinition $definition): EntityRecordValidationReport;

    public function validateUpdate(
        EntityRecordUpdateRequest $request,
        EntityDefinition $definition,
        array $existingValues,
    ): EntityRecordValidationReport;

    public function assertValid(EntityRecordValidationReport $report): void;
}
