<?php

namespace App\Services\Integration;

use App\Models\IntegrationMapping;
use App\Modules\Sdk\Integration\Data\IntegrationMappingDefinition;
use App\Modules\Sdk\Integration\Exceptions\IntegrationConnectorException;
use Illuminate\Support\Str;

class IntegrationMappingService
{
    public function __construct(
        private readonly IntegrationValidationService $validationService,
    ) {
    }

    /** @return list<IntegrationMappingDefinition> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array
    {
        $query = IntegrationMapping::query()
            ->orderByDesc('created_at')
            ->limit($limit);

        IntegrationMapper::applyOrganizationScope($query, $organizationId);
        IntegrationMapper::applyWorkspaceScope($query, $workspaceId);

        return $query->get()->map(fn (IntegrationMapping $model) => IntegrationMapper::toMapping($model))->all();
    }

    public function create(string $organizationId, ?string $workspaceId, IntegrationMappingDefinition $definition): IntegrationMappingDefinition
    {
        $this->validationService->validateMapping($definition);

        $model = IntegrationMapping::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'module_key' => $definition->moduleKey,
            'mapping_key' => $definition->mappingKey,
            'source_schema' => $definition->sourceSchema,
            'target_schema' => $definition->targetSchema,
            'mapping_json' => $definition->mapping,
            'transform_type' => $definition->transformType !== '' ? $definition->transformType : 'pass_through',
            'metadata' => $definition->metadata,
        ]);

        return IntegrationMapper::toMapping($model);
    }

    public function find(string $organizationId, ?string $workspaceId, string $publicId): ?IntegrationMappingDefinition
    {
        $query = IntegrationMapping::query()
            ->where('public_id', $publicId);

        IntegrationMapper::applyOrganizationScope($query, $organizationId);
        IntegrationMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        return $model ? IntegrationMapper::toMapping($model) : null;
    }

    public function findByKey(string $organizationId, ?string $workspaceId, string $mappingKey): ?IntegrationMappingDefinition
    {
        $query = IntegrationMapping::query()
            ->where('mapping_key', $mappingKey);

        IntegrationMapper::applyOrganizationScope($query, $organizationId);
        IntegrationMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        return $model ? IntegrationMapper::toMapping($model) : null;
    }

    public function resolveModel(string $organizationId, ?string $workspaceId, string $publicId): IntegrationMapping
    {
        $query = IntegrationMapping::query()
            ->where('public_id', $publicId);

        IntegrationMapper::applyOrganizationScope($query, $organizationId);
        IntegrationMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        if ($model === null) {
            throw new IntegrationConnectorException(sprintf('Mapping [%s] was not found.', $publicId));
        }

        return $model;
    }
}
