<?php

namespace App\Modules\Sdk\Integration\Contracts;

interface IntegrationEndpoint
{
    /** @return list<\App\Modules\Sdk\Integration\Data\IntegrationEndpointDefinition> */

    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array;

    public function create(string $organizationId, ?string $workspaceId, \App\Modules\Sdk\Integration\Data\IntegrationEndpointDefinition $definition): \App\Modules\Sdk\Integration\Data\IntegrationEndpointDefinition;
}
