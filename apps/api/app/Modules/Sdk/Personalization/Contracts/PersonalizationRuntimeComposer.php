<?php

namespace App\Modules\Sdk\Personalization\Contracts;

use App\Modules\Sdk\Personalization\Data\PersonalizationRuntimePayload;
use App\Support\Tenant\TenantContext;

interface PersonalizationRuntimeComposer
{
    public function compose(TenantContext $context): PersonalizationRuntimePayload;
}
