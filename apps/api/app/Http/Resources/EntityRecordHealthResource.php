<?php

namespace App\Http\Resources;

use App\Modules\Sdk\DataRepository\Data\EntityRecordHealthReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EntityRecordHealthReport */
class EntityRecordHealthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
