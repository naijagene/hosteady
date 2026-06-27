<?php

namespace App\Modules\Sdk\Entity\Contracts;

use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Modules\Sdk\Entity\Data\EntityMutationRequest;
use App\Modules\Sdk\Entity\Data\EntityValidationReport;

interface EntityValidator
{
    public function validate(EntityDefinition $definition): EntityValidationReport;

    public function validateMutation(
        EntityMutationRequest $request,
        EntityDefinition $definition,
    ): EntityValidationReport;
}
