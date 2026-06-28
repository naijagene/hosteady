<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Integration\Data\IntegrationStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IntegrationStatistics */
class IntegrationStatisticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof IntegrationStatistics) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
