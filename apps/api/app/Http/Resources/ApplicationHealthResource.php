<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Application\Data\ApplicationHealthReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ApplicationHealthReport */
class ApplicationHealthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof ApplicationHealthReport) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
