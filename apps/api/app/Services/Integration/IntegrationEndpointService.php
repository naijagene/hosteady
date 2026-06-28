<?php

namespace App\Services\Integration;

use App\Models\IntegrationEndpoint;
use App\Modules\Sdk\Integration\Contracts\IntegrationEndpoint as IntegrationEndpointContract;
use App\Modules\Sdk\Integration\Data\IntegrationEndpointDefinition;
use App\Modules\Sdk\Integration\Exceptions\IntegrationConnectorException;
use Illuminate\Support\Str;

class IntegrationEndpointService implements IntegrationEndpointContract
{
    public function __construct(
        private readonly IntegrationValidationService $validationService,
        private readonly IntegrationConnectorService $connectorService,
        private readonly IntegrationAuditRecorder $auditRecorder,
        private readonly IntegrationActivityService $activityService,
    ) {
    }

    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array
    {
        $query = IntegrationEndpoint::query()
            ->with('connector')
            ->orderByDesc('created_at')
            ->limit($limit);

        IntegrationMapper::applyOrganizationScope($query, $organizationId);
        IntegrationMapper::applyWorkspaceScope($query, $workspaceId);

        return $query->get()->map(function (IntegrationEndpoint $model) {
            return IntegrationMapper::toEndpoint($model, $model->connector?->public_id);
        })->all();
    }

    public function create(string $organizationId, ?string $workspaceId, IntegrationEndpointDefinition $definition): IntegrationEndpointDefinition
    {
        $this->validationService->validateEndpoint($definition);

        $connectorId = null;
        if ($definition->connectorPublicId !== null && $definition->connectorPublicId !== '') {
            $connector = $this->connectorService->resolveModel($organizationId, $workspaceId, $definition->connectorPublicId);
            $connectorId = $connector->id;
        }

        $endpointType = $definition->endpointType !== '' ? $definition->endpointType : 'outbound_webhook';
        if ($endpointType === 'webhook') {
            $endpointType = 'outbound_webhook';
        }

        $model = IntegrationEndpoint::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'integration_connector_id' => $connectorId,
            'endpoint_key' => $definition->endpointKey,
            'name' => $definition->name,
            'endpoint_type' => $endpointType,
            'direction' => $definition->direction !== '' ? $definition->direction : 'outbound',
            'status' => $definition->status !== '' ? $definition->status : 'enabled',
            'url_template' => $definition->urlTemplate,
            'method' => $definition->method ?? 'POST',
            'headers_json' => $definition->headers,
            'body_template_json' => $definition->bodyTemplate,
            'auth_reference' => $definition->authReference,
            'metadata' => $definition->metadata,
        ]);

        $created = IntegrationMapper::toEndpoint($model, $definition->connectorPublicId);
        $this->auditRecorder->recordEndpointCreated($created);
        $this->activityService->logEndpoint($model, 'created');

        return $created;
    }

    public function find(string $organizationId, ?string $workspaceId, string $publicId): ?IntegrationEndpointDefinition
    {
        $query = IntegrationEndpoint::query()
            ->with('connector')
            ->where('public_id', $publicId);

        IntegrationMapper::applyOrganizationScope($query, $organizationId);
        IntegrationMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        return $model ? IntegrationMapper::toEndpoint($model, $model->connector?->public_id) : null;
    }

    public function findByKey(string $organizationId, ?string $workspaceId, string $endpointKey): ?IntegrationEndpointDefinition
    {
        $query = IntegrationEndpoint::query()
            ->with('connector')
            ->where('endpoint_key', $endpointKey);

        IntegrationMapper::applyOrganizationScope($query, $organizationId);
        IntegrationMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        return $model ? IntegrationMapper::toEndpoint($model, $model->connector?->public_id) : null;
    }

    public function resolveModel(string $organizationId, ?string $workspaceId, string $publicId): IntegrationEndpoint
    {
        $query = IntegrationEndpoint::query()
            ->where('public_id', $publicId);

        IntegrationMapper::applyOrganizationScope($query, $organizationId);
        IntegrationMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        if ($model === null) {
            throw new IntegrationConnectorException(sprintf('Endpoint [%s] was not found.', $publicId));
        }

        return $model;
    }
}
