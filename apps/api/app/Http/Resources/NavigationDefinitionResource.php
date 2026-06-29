<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Navigation\Data\NavigationDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NavigationDefinition */
class NavigationDefinitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof NavigationDefinition) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
