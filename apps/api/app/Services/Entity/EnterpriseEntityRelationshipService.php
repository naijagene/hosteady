<?php

namespace App\Services\Entity;

use App\Models\EntityDefinition as EntityDefinitionModel;
use App\Models\EntityRelationship;
use App\Modules\Sdk\Entity\Data\EntityRelationshipDefinition;
use App\Modules\Sdk\Entity\Exceptions\EntityNotFoundException;
use Illuminate\Support\Facades\DB;

class EnterpriseEntityRelationshipService
{
    public function __construct(
        private readonly EnterpriseEntityAuditRecorder $auditRecorder,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function register(
        string $moduleKey,
        string $entityKey,
        EntityRelationshipDefinition|array $definition,
    ): array {
        if (is_array($definition)) {
            $definition = EntityRelationshipDefinition::fromArray(array_merge($definition, [
                'source_module_key' => $definition['source_module_key'] ?? $moduleKey,
                'source_entity_key' => $definition['source_entity_key'] ?? $entityKey,
            ]));
        }

        $source = EntityDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('entity_key', $entityKey)
            ->first();

        if ($source === null) {
            throw new EntityNotFoundException(sprintf(
                'Entity definition [%s.%s] was not found.',
                $moduleKey,
                $entityKey,
            ));
        }

        $targetId = null;
        if ($definition->targetModuleKey !== null && $definition->targetEntityKey !== null) {
            $target = EntityDefinitionModel::query()
                ->where('module_key', $definition->targetModuleKey)
                ->where('entity_key', $definition->targetEntityKey)
                ->first();

            $targetId = $target?->id;
        }

        return DB::transaction(function () use ($source, $targetId, $definition, $moduleKey, $entityKey) {
            $model = EntityRelationship::query()->create([
                'source_entity_definition_id' => $source->id,
                'target_entity_definition_id' => $targetId,
                'source_module_key' => $definition->sourceModuleKey ?: $moduleKey,
                'source_entity_key' => $definition->sourceEntityKey ?: $entityKey,
                'target_module_key' => $definition->targetModuleKey,
                'target_entity_key' => $definition->targetEntityKey,
                'relationship_key' => $definition->relationshipKey,
                'relationship_type' => $definition->relationshipType,
                'label' => $definition->label,
                'metadata' => $definition->metadata,
            ]);

            $this->auditRecorder->recordRelationshipRegistered($model);

            return EnterpriseEntityMapper::toRelationshipReference($model);
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForEntity(string $moduleKey, string $entityKey): array
    {
        $source = EntityDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('entity_key', $entityKey)
            ->first();

        if ($source === null) {
            throw new EntityNotFoundException(sprintf(
                'Entity definition [%s.%s] was not found.',
                $moduleKey,
                $entityKey,
            ));
        }

        return EntityRelationship::query()
            ->where('source_entity_definition_id', $source->id)
            ->orderBy('relationship_key')
            ->get()
            ->map(fn (EntityRelationship $model) => EnterpriseEntityMapper::toRelationshipReference($model))
            ->all();
    }
}
