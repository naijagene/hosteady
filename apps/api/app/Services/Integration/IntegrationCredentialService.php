<?php

namespace App\Services\Integration;

use App\Models\IntegrationCredential;
use App\Modules\Sdk\Integration\Contracts\IntegrationCredentialProvider;
use App\Modules\Sdk\Integration\Data\IntegrationCredentialReference;
use App\Modules\Sdk\Integration\Exceptions\IntegrationCredentialException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class IntegrationCredentialService implements IntegrationCredentialProvider
{
    public function store(
        string $organizationId,
        ?string $workspaceId,
        IntegrationCredentialReference $reference,
        array $payload,
    ): IntegrationCredentialReference {
        $model = IntegrationCredential::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'connector_key' => $reference->connectorKey,
            'credential_key' => $reference->credentialKey,
            'auth_type' => $reference->authType !== '' ? $reference->authType : 'none',
            'encrypted_payload' => Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)),
            'metadata' => $reference->metadata,
        ]);

        return IntegrationMapper::toCredentialReference($model);
    }

    public function rotate(
        string $organizationId,
        ?string $workspaceId,
        string $credentialKey,
        array $payload,
    ): IntegrationCredentialReference {
        $query = IntegrationCredential::query()
            ->where('organization_id', $organizationId)
            ->where('credential_key', $credentialKey);

        IntegrationMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        if ($model === null) {
            throw new IntegrationCredentialException(sprintf('Credential [%s] was not found.', $credentialKey));
        }

        $model->fill([
            'encrypted_payload' => Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)),
            'rotated_at' => now(),
        ])->save();

        return IntegrationMapper::toCredentialReference($model->fresh());
    }
}
