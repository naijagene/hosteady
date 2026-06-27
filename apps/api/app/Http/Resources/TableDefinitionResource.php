<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Table\Data\TableDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TableDefinition */
class TableDefinitionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var TableDefinition $definition */
        $definition = $this->resource;

        return $definition->toArray();
    }
}
