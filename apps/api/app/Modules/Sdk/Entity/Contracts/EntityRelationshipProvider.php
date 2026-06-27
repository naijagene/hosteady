<?php

namespace App\Modules\Sdk\Entity\Contracts;

use App\Modules\Sdk\Entity\Data\EntityRelationshipDefinition;

interface EntityRelationshipProvider
{
    public function moduleKey(): string;

    public function entityKey(): string;

    /**
     * @return list<EntityRelationshipDefinition>
     */
    public function relationships(): array;
}
