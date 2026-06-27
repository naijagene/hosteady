<?php

namespace App\Modules\Sdk\Entity\Contracts;

use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Modules\Sdk\Entity\Data\EntityMutationRequest;
use App\Modules\Sdk\Entity\Data\EntityMutationResult;
use App\Modules\Sdk\Entity\Data\EntityValidationReport;

interface EntityRepository
{
    public function resolveDefinition(string $moduleKey, string $entityKey): EntityDefinition;

    public function validateMutation(EntityMutationRequest $request): EntityValidationReport;

    public function mutate(EntityMutationRequest $request): EntityMutationResult;
}
