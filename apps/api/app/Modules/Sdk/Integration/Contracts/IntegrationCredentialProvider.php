<?php

namespace App\Modules\Sdk\Integration\Contracts;

interface IntegrationCredentialProvider
{
    public function store(string $organizationId, ?string $workspaceId, \App\Modules\Sdk\Integration\Data\IntegrationCredentialReference $reference, array $payload): \App\Modules\Sdk\Integration\Data\IntegrationCredentialReference;

    public function rotate(string $organizationId, ?string $workspaceId, string $credentialKey, array $payload): \App\Modules\Sdk\Integration\Data\IntegrationCredentialReference;
}
