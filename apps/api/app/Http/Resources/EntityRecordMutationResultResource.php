<?php

namespace App\Http\Resources;

use App\Modules\Sdk\DataRepository\Data\EntityRecordMutationResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EntityRecordMutationResult */
class EntityRecordMutationResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
