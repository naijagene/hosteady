<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Integration\Data\IntegrationDispatchResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IntegrationDispatchResult */
class IntegrationDispatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof IntegrationDispatchResult) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
