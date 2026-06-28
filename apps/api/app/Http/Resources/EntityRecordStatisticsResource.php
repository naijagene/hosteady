<?php

namespace App\Http\Resources;

use App\Modules\Sdk\DataRepository\Data\EntityRecordStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EntityRecordStatistics */
class EntityRecordStatisticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
