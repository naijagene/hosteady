<?php

namespace App\Modules\Sdk\Application\Contracts;

interface ApplicationRuntime
{
    public function load(\App\Support\Tenant\TenantContext $context): \App\Modules\Sdk\Application\Data\ApplicationRuntimeMetadata;

    /** @return list<\App\Modules\Sdk\Application\Data\ApplicationDefinition> */
    public function listApplications(\App\Support\Tenant\TenantContext $context): array;
}
