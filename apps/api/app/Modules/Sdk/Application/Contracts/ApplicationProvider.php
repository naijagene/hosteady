<?php

namespace App\Modules\Sdk\Application\Contracts;

interface ApplicationProvider
{
    public function application(\App\Support\Tenant\TenantContext $context, string $applicationKey): \App\Modules\Sdk\Application\Data\ApplicationDefinition;
}
