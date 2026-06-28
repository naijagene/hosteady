<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Rules\Data\RuleDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RuleDefinition */
class RuleDefinitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof RuleDefinition) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
