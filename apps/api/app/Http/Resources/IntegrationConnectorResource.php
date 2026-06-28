<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Integration\Data\IntegrationConnectorDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IntegrationConnectorDefinition */
class IntegrationConnectorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof IntegrationConnectorDefinition) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
