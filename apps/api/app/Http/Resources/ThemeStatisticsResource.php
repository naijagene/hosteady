<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Theme\Data\ThemeStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ThemeStatistics */
class ThemeStatisticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof ThemeStatistics) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
