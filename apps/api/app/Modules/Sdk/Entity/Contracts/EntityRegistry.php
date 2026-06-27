<?php

namespace App\Modules\Sdk\Entity\Contracts;

use App\Modules\Sdk\Entity\Data\EntityDefinition;

interface EntityRegistry
{
    public function register(mixed $source): EntityDefinition;

    public function update(EntityDefinition $definition): EntityDefinition;

    public function find(string $moduleKey, string $entityKey): ?EntityDefinition;

    /**
     * @return list<EntityDefinition>
     */
    public function list(?string $moduleKey = null): array;

    /**
     * @param  list<array<string, mixed>>  $entities
     * @return list<EntityDefinition>
     */
    public function registerFromManifestEntities(array $entities, string $moduleKey): array;
}
