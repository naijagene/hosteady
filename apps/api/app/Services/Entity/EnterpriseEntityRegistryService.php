<?php

namespace App\Services\Entity;

use App\Models\EntityDefinition as EntityDefinitionModel;
use App\Modules\Sdk\Entity\Contracts\EntityRegistry;
use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Modules\Sdk\Entity\EnterpriseEntity;
use App\Modules\Sdk\Entity\Exceptions\EntityNotFoundException;
use App\Modules\Sdk\Entity\Exceptions\EntityRegistryException;
use Illuminate\Support\Facades\DB;

class EnterpriseEntityRegistryService implements EntityRegistry
{
    public function __construct(
        private readonly EnterpriseEntityValidationService $validator,
        private readonly EnterpriseEntityAuditRecorder $auditRecorder,
        private readonly EnterpriseEntitySearchIndexer $searchIndexer,
    ) {
    }

    public function register(mixed $source): EntityDefinition
    {
        $definition = $this->resolveDefinitionSource($source);
        $this->validator->assertValid($definition);

        if (EntityDefinitionModel::query()
            ->where('module_key', $definition->moduleKey)
            ->where('entity_key', $definition->entityKey)
            ->exists()) {
            throw new EntityRegistryException(sprintf(
                'Entity definition [%s.%s] is already registered.',
                $definition->moduleKey,
                $definition->entityKey,
            ));
        }

        return DB::transaction(function () use ($definition) {
            $model = new EntityDefinitionModel;
            EnterpriseEntityMapper::applyDefinition($model, $definition);
            $model->save();

            $this->auditRecorder->recordDefinitionRegistered($model);
            $this->searchIndexer->indexDefinitionBestEffort($model);

            return EnterpriseEntityMapper::toDefinition($model);
        });
    }

    public function update(EntityDefinition $definition): EntityDefinition
    {
        $this->validator->assertValid($definition);

        $model = EntityDefinitionModel::query()
            ->where('module_key', $definition->moduleKey)
            ->where('entity_key', $definition->entityKey)
            ->first();

        if ($model === null) {
            throw new EntityNotFoundException(sprintf(
                'Entity definition [%s.%s] was not found.',
                $definition->moduleKey,
                $definition->entityKey,
            ));
        }

        return DB::transaction(function () use ($model, $definition) {
            EnterpriseEntityMapper::applyDefinition($model, $definition);
            $model->save();

            $this->auditRecorder->recordDefinitionUpdated($model);
            $this->searchIndexer->indexDefinitionBestEffort($model);

            return EnterpriseEntityMapper::toDefinition($model);
        });
    }

    public function find(string $moduleKey, string $entityKey): ?EntityDefinition
    {
        $model = EntityDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('entity_key', $entityKey)
            ->first();

        return $model === null ? null : EnterpriseEntityMapper::toDefinition($model);
    }

    /**
     * @return list<EntityDefinition>
     */
    public function list(?string $moduleKey = null): array
    {
        $query = EntityDefinitionModel::query()->orderBy('module_key')->orderBy('entity_key');

        if ($moduleKey !== null) {
            $query->where('module_key', $moduleKey);
        }

        return $query->get()
            ->map(fn (EntityDefinitionModel $model) => EnterpriseEntityMapper::toDefinition($model))
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $entities
     * @return list<EntityDefinition>
     */
    public function registerFromManifestEntities(array $entities, string $moduleKey): array
    {
        $registered = [];

        foreach ($entities as $entity) {
            if (! is_array($entity)) {
                continue;
            }

            $payload = array_merge($entity, ['module_key' => $moduleKey]);
            $entityKey = (string) ($payload['entity_key'] ?? $payload['key'] ?? '');

            if ($entityKey === '') {
                continue;
            }

            if ($this->find($moduleKey, $entityKey) !== null) {
                continue;
            }

            $registered[] = $this->register(EntityDefinition::fromArray($payload));
        }

        return $registered;
    }

    public function findByPublicId(string $publicId): ?EntityDefinition
    {
        $model = EntityDefinitionModel::query()->where('public_id', $publicId)->first();

        return $model === null ? null : EnterpriseEntityMapper::toDefinition($model);
    }

    private function resolveDefinitionSource(mixed $source): EntityDefinition
    {
        if ($source instanceof EntityDefinition) {
            return $source;
        }

        if (is_array($source)) {
            return EntityDefinition::fromArray($source);
        }

        if ($source instanceof EnterpriseEntity) {
            return $source->toDefinition();
        }

        if (is_string($source) && class_exists($source) && is_subclass_of($source, EnterpriseEntity::class)) {
            /** @var EnterpriseEntity $instance */
            $instance = app()->bound($source) ? app($source) : new $source;

            return $instance->toDefinition();
        }

        throw new EntityRegistryException('Unsupported entity definition source.');
    }
}
