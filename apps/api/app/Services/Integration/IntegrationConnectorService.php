<?php

namespace App\Services\Integration;

use App\Models\IntegrationConnector;
use App\Modules\Sdk\Integration\Contracts\IntegrationConnector as IntegrationConnectorContract;
use App\Modules\Sdk\Integration\Data\IntegrationConnectorDefinition;
use App\Modules\Sdk\Integration\Exceptions\IntegrationConnectorException;
use Illuminate\Support\Str;

class IntegrationConnectorService implements IntegrationConnectorContract
{
    public function __construct(
        private readonly IntegrationValidationService $validationService,
        private readonly IntegrationAuditRecorder $auditRecorder,
        private readonly IntegrationActivityService $activityService,
    ) {
    }

    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array
    {
        $query = IntegrationConnector::query()
            ->orderByDesc('created_at')
            ->limit($limit);

        IntegrationMapper::applyOrganizationScope($query, $organizationId);
        IntegrationMapper::applyWorkspaceScope($query, $workspaceId);

        return $query->get()->map(fn (IntegrationConnector $model) => IntegrationMapper::toConnector($model))->all();
    }

    public function create(string $organizationId, ?string $workspaceId, IntegrationConnectorDefinition $definition): IntegrationConnectorDefinition
    {
        $this->validationService->validateConnector($definition);

        $model = IntegrationConnector::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'module_key' => $definition->moduleKey,
            'connector_key' => $definition->connectorKey,
            'name' => $definition->name,
            'description' => $definition->description,
            'connector_type' => $definition->connectorType !== '' ? $definition->connectorType : 'webhook',
            'auth_type' => $definition->authType !== '' ? $definition->authType : 'none',
            'status' => $definition->status !== '' ? $definition->status : 'enabled',
            'config_json' => $definition->config,
            'metadata' => $definition->metadata,
        ]);

        $created = IntegrationMapper::toConnector($model);
        $this->auditRecorder->recordConnectorCreated($created);
        $this->activityService->logConnector($model, 'created');

        return $created;
    }

    public function find(string $organizationId, ?string $workspaceId, string $publicId): ?IntegrationConnectorDefinition
    {
        $query = IntegrationConnector::query()
            ->where('public_id', $publicId);

        IntegrationMapper::applyOrganizationScope($query, $organizationId);
        IntegrationMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        return $model ? IntegrationMapper::toConnector($model) : null;
    }

    public function findByKey(string $organizationId, ?string $workspaceId, string $connectorKey): ?IntegrationConnectorDefinition
    {
        $query = IntegrationConnector::query()
            ->where('connector_key', $connectorKey);

        IntegrationMapper::applyOrganizationScope($query, $organizationId);
        IntegrationMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        return $model ? IntegrationMapper::toConnector($model) : null;
    }

    public function resolveModel(string $organizationId, ?string $workspaceId, string $publicId): IntegrationConnector
    {
        $query = IntegrationConnector::query()
            ->where('public_id', $publicId);

        IntegrationMapper::applyOrganizationScope($query, $organizationId);
        IntegrationMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        if ($model === null) {
            throw new IntegrationConnectorException(sprintf('Connector [%s] was not found.', $publicId));
        }

        return $model;
    }
}
