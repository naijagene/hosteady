<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Application\Data\ApplicationDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ApplicationDefinition */
class ApplicationRuntimeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof ApplicationDefinition) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
