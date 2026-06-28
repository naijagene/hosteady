<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Document\Data\DocumentScanResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DocumentScanResult */
class DocumentScanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
