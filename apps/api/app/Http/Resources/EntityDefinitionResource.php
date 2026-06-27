<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Entity\Data\EntityDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EntityDefinition */
class EntityDefinitionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var EntityDefinition $definition */
        $definition = $this->resource;

        return $definition->toArray();
    }
}
