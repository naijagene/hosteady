<?php

namespace App\Modules\Sdk\Application\Contracts;

interface ApplicationLifecycle
{
    public function enable(string $organizationId, ?string $workspaceId, string $publicId): \App\Modules\Sdk\Application\Data\ApplicationDefinition;

    public function disable(string $organizationId, ?string $workspaceId, string $publicId): \App\Modules\Sdk\Application\Data\ApplicationDefinition;
}
