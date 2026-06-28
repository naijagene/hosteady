<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Document\Data\DocumentThumbnail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DocumentThumbnail */
class DocumentThumbnailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
