<?php

namespace App\Modules\Sdk\Application\Contracts;

interface ApplicationRegistry
{
    public function register(string $organizationId, ?string $workspaceId, \App\Modules\Sdk\Application\Data\ApplicationDefinition $definition): \App\Modules\Sdk\Application\Data\ApplicationDefinition;

    /** @return list<\App\Modules\Sdk\Application\Data\ApplicationDefinition> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array;

    public function findByPublicId(string $organizationId, ?string $workspaceId, string $publicId): \App\Modules\Sdk\Application\Data\ApplicationDefinition;
}
