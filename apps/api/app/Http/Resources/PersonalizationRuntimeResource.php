<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Personalization\Data\PersonalizationRuntimePayload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonalizationRuntimeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof PersonalizationRuntimePayload) {
            return $this->resource->toApiArray();
        }

        return is_array($this->resource) ? $this->resource : (array) $this->resource;
    }
}
