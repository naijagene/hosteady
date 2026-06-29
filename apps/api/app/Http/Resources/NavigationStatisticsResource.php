<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Navigation\Data\NavigationStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NavigationStatistics */
class NavigationStatisticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof NavigationStatistics) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
