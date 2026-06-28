<?php

namespace App\Http\Resources;

use App\Modules\Sdk\DataRepository\Data\EntityRecordQueryResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EntityRecordQueryResult */
class EntityRecordQueryResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
