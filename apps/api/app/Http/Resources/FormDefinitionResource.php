<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Form\Data\FormDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FormDefinition */
class FormDefinitionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var FormDefinition $definition */
        $definition = $this->resource;

        return $definition->toArray();
    }
}
