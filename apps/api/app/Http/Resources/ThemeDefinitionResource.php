<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Theme\Data\ThemeDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ThemeDefinition */
class ThemeDefinitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof ThemeDefinition) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
