<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Document\Data\DocumentOcrResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DocumentOcrResult */
class DocumentOcrResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
