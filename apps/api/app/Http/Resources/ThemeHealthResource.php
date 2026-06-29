<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Theme\Data\ThemeHealthReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ThemeHealthReport */
class ThemeHealthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof ThemeHealthReport) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
