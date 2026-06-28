<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Ui\Data\UiStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UiStatistics */
class UiStatisticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof UiStatistics) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
