<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Application\Data\ApplicationStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ApplicationStatistics */
class ApplicationStatisticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof ApplicationStatistics) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
