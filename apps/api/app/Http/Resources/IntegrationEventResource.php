<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Integration\Data\IntegrationEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IntegrationEvent */
class IntegrationEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof IntegrationEvent) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
