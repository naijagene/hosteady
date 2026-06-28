<?php

namespace App\Http\Resources;

use App\Modules\Sdk\DataRepository\Data\EntityRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EntityRecord */
class EntityRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
