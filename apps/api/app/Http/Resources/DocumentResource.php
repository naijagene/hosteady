<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Document\Data\DocumentReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DocumentReference */
class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
