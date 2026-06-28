<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Document\Data\DocumentStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DocumentStatistics */
class DocumentStatisticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
