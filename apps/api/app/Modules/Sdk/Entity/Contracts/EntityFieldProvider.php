<?php

namespace App\Modules\Sdk\Entity\Contracts;

use App\Modules\Sdk\Entity\Data\EntityFieldDefinition;

interface EntityFieldProvider
{
    /**
     * @return list<EntityFieldDefinition>
     */
    public function fields(): array;
}
