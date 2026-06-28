<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Document\Data\DocumentHealthReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DocumentHealthReport */
class DocumentHealthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
