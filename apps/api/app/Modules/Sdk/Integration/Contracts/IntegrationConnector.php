<?php

namespace App\Modules\Sdk\Integration\Contracts;

interface IntegrationConnector
{
    /** @return list<\App\Modules\Sdk\Integration\Data\IntegrationConnectorDefinition> */

    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array;

    public function create(string $organizationId, ?string $workspaceId, \App\Modules\Sdk\Integration\Data\IntegrationConnectorDefinition $definition): \App\Modules\Sdk\Integration\Data\IntegrationConnectorDefinition;

    public function find(string $organizationId, ?string $workspaceId, string $publicId): ?\App\Modules\Sdk\Integration\Data\IntegrationConnectorDefinition;
}
